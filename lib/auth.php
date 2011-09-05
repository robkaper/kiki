<?

/**
* @class Auth
* Utility class to assist authentication. Provides cookie creation and
* verification as well as password hashing with multiple pepper and salt
* iterations.
*
* @see http://raza.narfum.org/post/1/user-authentication-with-a-secure-cookie-protocol-in-php/
*
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Auth
{
  /**
  * Calculates the hash of a password.
  */
  public static function passwordHash( $password, $salt = null )
  {
    $pepper =  Config::$passwordHashPepper;
    $iterations = Config::$passwordHashIterations;

      $hash = $password;
      for( $i=0; $i<$iterations; ++$i )
        $hash = sha1( $salt. $pepper. $hash );
      return $hash;
  }

  private static function generateCookie( $id, $expires )
  {
    $key = hash_hmac( 'md5', $id. $expires, Config::$authCookiePepper );
    $hash = hash_hmac( 'md5', $id. $expires, $key );
    return $id. '|'. $expires. '|'. $hash;
  }

  public static function setCookie( $id )
  {
    // @todo make cookie length configurable (preferably by end user, with available options configurable by site administrator)
    $expires = time() + ( 7 * 86400 );

    $cookie = self::generateCookie( $id, $expires );
    Log::debug( "setting cookie: id($id), expires($expires), cookie($cookie)" );
    setcookie( Config::$authCookieName, $cookie, $expires, '/', $_SERVER['HTTP_HOST'] );
  }

  /**
  * @return int ID stored in cookie if valid, 0 otherwise
  */
  public static function validateCookie()
  {
    if ( !isset($_COOKIE[Config::$authCookieName]) )
    {
      Log::debug( "Auth::validateCookie: not set" );
      return false;
    }

    list( $id, $expires, $hmac )  = explode( '|', $_COOKIE[Config::$authCookieName] );

    if ( time() > $expires )
    {
      Log::debug( "Auth::validateCookie: expired, time(". time(). "), expires($expires)" );
      return false;
    }

    $key = hash_hmac( 'md5', $id. $expires, Config::$authCookiePepper );
    $hash = hash_hmac( 'md5', $id. $expires, $key );

    Log::debug( "Auth::validateCookie: hmac($hmac), hash($hash)" );
    $valid = ($hmac==$hash);
    if ( $valid )
    {
      // Refresh cookie if it's more than a day old
      if ( time() + 86400 > $expires )
        setCookie($id);

      // @todo remove return 0, it's here for debugging login/registration
      return 0;
      return $id;
    }
    
    return 0;
  }
}

?>