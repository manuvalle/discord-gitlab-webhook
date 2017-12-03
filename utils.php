<?php

/**
 * Utilities
 */
class Utils
{
  /**
   * Return decoded json from input
   * @method getData
   * @return json
   */
  public function getInputData() {
    return json_decode(file_get_contents('php://input'), true);
  }

  /**
   * Pluralize a word based on the number passed
   * @method pluralize
   * @param  integer    $quantity
   * @param  string     $singular
   * @param  boolean    $plural
   * @return string
   */
  public function pluralize($quantity, $singular, $plural=null) {
    if($quantity == 1 || !strlen($singular)) return $singular;
    if($plural !== null) return $plural;

    $last_letter = strtolower($singular[strlen($singular)-1]);
    switch($last_letter) {
      case 'y':
        return substr($singular,0,-1).'ies';
      case 's':
        return $singular.'es';
      default:
        return $singular.'s';
    }
  }

  /**
   * Truncate text without cutting words
   * @method truncate
   * @param  string   $string
   * @param  integer  $max
   * @return string
   */
  public function truncate($string, $max = 20)
  {
    $tok = strtok($string,' ');
    $string = '';

    while($tok !== false && strlen($string) < $max)
    {
      if (strlen($string) + strlen($tok) <= $max)
        $string .= $tok .' ';
      else
        break;
      $tok = strtok(' ');
    }
    
    return trim($string);
  }
}
