<?php

/**
 * Utility class for various methods that don't fit Kiki's object model (yet).
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Misc
{
  /**
  * Provides a description of the difference between a timestamp and the current time.
  * @param int $time the time to compare the current time to
  * @return string description of the difference in time
  * @warning Only supports comparison to times in the past.
  * @todo add i18n support
  */
  public static function relativeTime( $time, $now = null )
  {
    if ( $now === null )
      $now = time();
    if ( !is_numeric($time) )
      $time = strtotime( $time );
    $delta = $now - $time;
    $absDelta = abs($delta);

    if ( $absDelta < 60 )
      return "less than a minute";
    else if ( $absDelta < 120 )
      return "one minute";
    else if ( $absDelta < (4*60) )
      return "a few minutes";
    else if ( $absDelta < (60*60) )
      return (int)($absDelta/60). " minutes";
    else if ( $absDelta < (120*60) )
      return "one hour";
    else if ( $absDelta < (24*60*60) )
      return (int)($absDelta/3600). " hours";
    else if ( $absDelta < (48*60*60) )
      return "one day";
    else
      return (int)($absDelta/86400). " days";
  }

  /**
  * Converts a string into a version safe for use in URIs.
  * @param string $str input string
  * @return string the URI-safe string
  */
  public static function uriSafe( $str )
  {
      // Convert to ASCII with common translations
      $uri = iconv("utf-8", "ascii//TRANSLIT", $str);
      // Substitutes anything but letters, numbers and '_' with separator ('-')
      $uri = preg_replace('~[^\\pL0-9_]+~u', '-', $uri);
      // Remove seperators from the beginning and end
      $uri = trim($uri, "-");
      // Lowercase
      $uri = strtolower($uri);
      // Keep only letters, numbers, '_' and separator
      $uri = preg_replace('~[^-a-z0-9_]+~', '', $uri);
      return $uri;
  }

  // $dates = array( '2012' => '2012-08-06' );
  public static function countdown( $datesPerYear )
  {
    $year = date("Y");
    $day = date("z");

    // Find date
    $dayTarget = isset($datesPerYear[$year]) ? strtotime($datesPerYear[$year]) : false;
    $yearDiff = 0;

    // Find date next year
    if ( $dayTarget === false )
    {
      $dayTarget = isset($datesPerYear[$year+1]) ? strtotime($datesPerYear[$year+1]) : false;
      $yearDiff = date("z", mktime(0,0,0,12,31,$year)) + 1;
    }

    Log::debug( "$dayTarget, $day, $yearDiff, ". date("z", $dayTarget) );
    if ( $dayTarget !== false )
      return date("z", $dayTarget) - $day + $yearDiff;
    
    return false;
  }

}
