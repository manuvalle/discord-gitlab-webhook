<?php

require_once '../bootstrap.php';

if (!isset($_GET['provider']))
  throw new \Exception('You must select a provider.');

switch ($_GET['provider'])
{
  case 'gitlab': {
    if ((!isset($_SERVER['HTTP_X_GITLAB_TOKEN'])) || (getenv('TOKEN') !== $_SERVER['HTTP_X_GITLAB_TOKEN'])) {
      header("HTTP/1.1 401 Unauthorized");
      echo 'Invalid token provided.';
      exit;
    }

    return new Gitlab($utils->getInputData(), $utils, $webhook, $embed);
  }
  default:
    throw new \Exception('Unknown provider.');
}
