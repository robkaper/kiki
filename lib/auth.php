<?php

/**
 * Utility class to assist authentication. Provides cookie creation and
 * verification as well as password hashing with pepper.
 *
 * @see http://raza.narfum.org/post/1/user-authentication-with-a-secure-cookie-protocol-in-php/
 *
 * @class Auth
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Auth
{
  /**
   * Calculates the hash of a password.
   *
   * @param string $password The password to be hashed.
   * @param string $salt The salt to use for this particular hash.
   *
   * @return string The calculated hash.
   */
  public static function hashPassword( $password )
  {
    return password_hash( self::passwordPepper($password), PASSWORD_DEFAULT );
  }

  public static function passwordPepper( $password )
  {
    return hash_hmac( 'sha512', $password, Config::$passwordHashPepper );
  }

  public static function verifyPassword( $password, $hash )
  {
    return password_verify( self::passwordPepper($password), $hash );
  }

  /**
   * Generates a cookie value based on ID, expire date and hash.
   *
   * @param string $id The value to be stored in the cookie.
   * @param int $expires The time (Epoch seconds) of expiration.
   *
   * @return string The cookie value: id, expiration and hash.
   */
  private static function generateCookie( $id, $expires )
  {
    $key = hash_hmac( 'sha256', $id. $expires, Config::$authCookiePepper );
    $hash = hash_hmac( 'sha256', $id. $expires, $key );
    return $id. '|'. $expires. '|'. $hash;
  }

  /**
   * Generates and sets a cookie.
   *
   * @param string $id The value to be stored in the cookie.
   *
   * @return void
   */
  public static function setCookie( $id, $beyondSession = false )
  {
    // TODO: make cookie length configurable (preferably by end user, with
    // available options configurable by site administrator)

    $expires = $beyondSession ? ( time() + (14*86400) ) : 0;
    $cookie = self::generateCookie( $id, $expires );

    Log::debug( "setting cookie: id($id), expires($expires), cookie($cookie)" );
    setcookie( Config::$authCookieName, $cookie, $expires, '/', $_SERVER['HTTP_HOST'], ($_SERVER['HTTPS'] == 'on'), true );
  }

  /**
   * Validates a cookie as generated by generateCookie() and set by
   * setCookie(). Also refreshes the cookie in case it is about to expire.
   *
   * @see @var Config::$authCookieName
   * 
   * @return string Value stored in cookie if valid and not expired, otherwise 0.
   */
  public static function validateCookie()
  {
    if ( !isset($_COOKIE[Config::$authCookieName]) )
      return 0;

    list( $id, $expires, $hmac )  = explode( '|', $_COOKIE[Config::$authCookieName] );

    if ( $expires!=0 && time() > $expires )
    {
      Log::debug( "Auth::validateCookie: expired, time(". time(). "), expires($expires)" );
      return 0;
    }

    $key = hash_hmac( 'sha256', $id. $expires, Config::$authCookiePepper );
    $hash = hash_hmac( 'sha256', $id. $expires, $key );

    $valid = hash_equals($hmac, $hash);
    if ( $valid )
    {
      // Refresh cookie if it's more than a day old
      if ( $expires!=0 && time() + 86400 > $expires )
        self::setCookie($id, true);

      return $id;
    }
    
    return 0;
  }
}
