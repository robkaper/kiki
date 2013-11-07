<?php

/**
 * Base 62 encoding and decoding.
 *
 * @class Base64
 * @package Kiki
 * @author Andy Huang
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2009 Andy Huang
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 *
 * @license This code is not distributed under any specific license. The
 * original author Andy Huang distributed it under the terms outlined below:
 * - You may use these code as part of your application, even if it is a commercial product
 * - You may modify these code to suite your application, even if it is a commercial product
 * - You may sell your commercial product derived from these code
 * - You may donate to me if you are some how able to get a hold of me, but that's not required
 * - You may link back to the original article for reference, but do not hotlink the source file
 * - This line is intentionally added to differentiate from LGPL, or other similar licensing terms
 * - You must at all time retain this copyright message and terms in your code
 */

namespace Kiki;

class Base62
{
  private static $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  private static $base = 62;

  public static function encode($var)
  {
    $stack = array();

    while ( bccomp($var, 0) != 0 )
    {
      $remainder = bcmod($var, self::$base);
      $var = bcdiv( bcsub($var, $remainder), self::$base );
      array_push($stack, self::$characters[$remainder]);
    }
    return implode('', array_reverse($stack));
  }
  
  public static function decode($var)
  {
    $length = strlen($var);
    $result = 0;
    for($i=0; $i<$length; $i++)
      $result = bcadd($result, bcmul(self::getDigit($var[$i]), bcpow(self::$base, ($length-($i+1)))));
    return $result;
  }

  private function getDigit($var)
  {
    return strpos(self::$characters, $var);
  }
}

?>