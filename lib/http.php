<?

/**
 * class Http
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */
 
class Http
{
  /**
   * Sends raw HTTP headers.
   *
   * Defaults to 200, but setting any unrecognised code in setHttpStatus() results in 500.
   *
   * @todo Support all HTTP/1.1 codes from RFC2616 when sending the status code.  Currently only 200, 301, 302, 404 and 500 are supported.
   */
  public static function sendHeaders( $status = 200 )
  {
    switch( $status )
    {
      case 200:
        header( $_SERVER['SERVER_PROTOCOL']. ' 200 OK', 200 );
        header( 'Content-Type: text/html; charset=utf-8' );
        break;

      case 301:
        header( $_SERVER['SERVER_PROTOCOL']. ' 301 Moved Permanently', 301 );
        break;

      case 302:
        header( $_SERVER['SERVER_PROTOCOL']. ' 302 Found', 302 );
        break;

      case 303:
        header( $_SERVER['SERVER_PROTOCOL']. ' 303 See Other', 303 );
        break;

      case 404:
        header( $_SERVER['SERVER_PROTOCOL']. ' 404 Not Found', 404 );
        header( 'Content-Type: text/html; charset=utf-8' );
        break;

      case 503:
        header( $_SERVER['SERVER_PROTOCOL']. ' 503 Service Unavailable', 503 );
        header( 'Content-Type: text/html; charset=utf-8' );
        break;

      case 500:
      default:
        header( $_SERVER['SERVER_PROTOCOL']. ' 500 Internal Server Error', 500 );
        break;
    }
  }
}
