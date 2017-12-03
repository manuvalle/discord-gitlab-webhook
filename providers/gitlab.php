<?php

/**
 * Gitlab webook provider
 */
class Gitlab {
  protected $data;
  protected $utils;
  protected $webhook;
  protected $embed;

  /**
   * Saves the class arguments as a global variables
   * @method __construct
   * @param  json      $data The decoded json from gitlab.com
   */
  public function __construct($data, $utils, $webhook, $embed)
  {
    $this->data = $data;
    $this->utils = $utils;
    $this->webhook = $webhook;
    $this->embed = $embed;

    return $this->determineAction();
  }

  /**
   * Determines what action to care depending on the event passed by gitlab.com
   * @method determineAction
   * @return function          The function returned is based on the event name
   */
  private function determineAction() {
    if (!$this->data) {
      throw new \Exception("Invalid data");
    }

    $function_name = implode('', array_map('ucfirst', explode('_', $this->data['object_kind'])));
    $function_name = 'on' . $function_name;

    file_put_contents("$function_name.log", print_r($this->data, true));

    if (!method_exists($this, $function_name)) {
      throw new \Exception("The event '$function_name' doesn't exist.");
    }

    return $this->$function_name();
  }

  /**
   * onPush event
   * @method onPush
   * @return function send webhook to discord
   */
  private function onPush() {
    $title = "[".
      $this->data['repository']['name'] .":".
      substr($this->data['ref'], strrpos($this->data['ref'], '/') + 1) ."] ".
      $this->data['total_commits_count'] ." new ".
      $this->utils->pluralize($this->data['total_commits_count'], 'commit');

    $description = '';

    foreach ($this->data['commits'] as $commit) {
      $id = substr($commit["id"], 0, 7);
      $message = preg_replace( "/\r|\n/", "", $commit["message"]);

      $description .= "[`". $id ."`]".
        "(". $commit["url"] .") ". $message ." - ". $commit["author"]["name"] ."\n";
    }

    $this->embed->title($title);
    $this->embed->url($this->data['commits'][0]['url']);
    $this->embed->color(7506394);
    $this->embed->description($description);
    $this->embed->author(
      $this->data['user_username'],
      'https://gitlab.com/'. $this->data['user_username'],
      $this->data['user_avatar']
    );

    return $this->sendWebhook();
  }

  /**
   * onIssue event
   * @method onIssue
   * @return function  send webhook to discord
   */
  private function onIssue()
  {
    $title = "[".
      $this->data['project']['path_with_namespace'] ."] ".
      "Issue opened: #". $this->data['object_attributes']['iid'] ." ".
      $this->data['object_attributes']['title'];

    $description = $this->utils->truncate($this->data['object_attributes']['description'], 500);
    if (strlen($this->data['object_attributes']['description']) > 500)
      $description .= "\n\n...";

    $this->embed->title($title);
    $this->embed->url($this->data['object_attributes']['url']);
    $this->embed->color(15426592);
    $this->embed->description($description);
    $this->embed->author(
      $this->data['user']['username'],
      'https://gitlab.com/'. $this->data['user']['username'],
      $this->data['user']['avatar_url']
    );

    return $this->sendWebhook();
  }

  /**
   * onNote event
   * @method onNote
   * @return function send webhook to discord
   */
  private function onNote()
  {
    $description = $this->utils->truncate($this->data['object_attributes']['note'], 500);
    if (strlen($this->data['object_attributes']['note']) > 500)
      $description .= "\n\n...";

    $title = "[". $this->data['project']['path_with_namespace'] ."] ";

    switch ($this->data['object_attributes']['noteable_type'])
    {
      case 'Issue': {
        $title .= "New comment on Issue #". $this->data['issue']['iid'] .": ".
          $this->data['issue']['title'];
        break;
      }

      case 'MergeRequest': {
        $title .= "New comment on Pull Request #". $this->data['merge_request']['iid'] .": ".
          $this->data['merge_request']['title'];
        break;
      }

      case 'Snippet': {
        $title .= "New comment on Snippet #". $this->data['snippet']['id'] .": ".
          $this->data['snippet']['title'];
        break;
      }

      case 'Commit': {
        $title .= "New comment on Commit";
        $currentDescription = $description;
        $id = substr($this->data['commit']["id"], 0, 7);
        $message = preg_replace( "/\r|\n/", "", $this->data['commit']["message"]);

        $description = "[`". $id ."`]".
          "(". $this->data['commit']["url"] .") ". $message ." - ". $this->data['commit']["author"]["name"] .
          "\n\n" . $currentDescription;
        break;
      }
    }

    $this->embed->title($title);
    $this->embed->url($this->data['object_attributes']['url']);
    $this->embed->color(15109472);
    $this->embed->description($description);
    $this->embed->author(
      $this->data['user']['username'],
      'https://gitlab.com/'. $this->data['user']['username'],
      $this->data['user']['avatar_url']
    );

    return $this->sendWebhook();
  }

  /**
   * onMergeRequest event
   * @method onMergeRequest
   * @return function         send webhook to discord
   */
  private function onMergeRequest()
  {
    $title = "[".
      $this->data['project']['path_with_namespace'] ."] ".
      "Pull request ". $this->data['object_attributes']['state'] .": #".
      $this->data['object_attributes']['iid'] ." ".
      $this->data['object_attributes']['title'];

    $description = $this->utils->truncate($this->data['object_attributes']['description'], 500);
    if (strlen($this->data['object_attributes']['description']) > 500)
      $description .= "\n\n...";

    $this->embed->color(5198940);
    if ($this->data['object_attributes']['state'] == "opened")
      $this->embed->color(38912);

    $this->embed->title($title);
    $this->embed->url($this->data['object_attributes']['url']);
    $this->embed->description($description);
    $this->embed->author(
      $this->data['user']['username'],
      'https://gitlab.com/'. $this->data['user']['username'],
      $this->data['user']['avatar_url']
    );

    return $this->sendWebhook();
  }

  /**
   * sendWebhook event
   * @method sendWebhook
   * @return function      send webhook to discord
   */
  private function sendWebhook()
  {
    return $this->webhook
      ->username('Gitlab')
      ->avatar('https://about.gitlab.com/images/press/logo/logo.png')
      ->embed($this->embed)
      ->send();
  }
}
