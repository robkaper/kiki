<?php

/**
 * class Http
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;
 
class Http
{
  /**
   * Sends raw HTTP headers.
   *
   * Argument defaults to 200.  Trying to set any code not in RFC 9110
   * results in 500, this is intentional.
   *
   * Note that the fallback controller in the router actually defaults to 404.
   */
  public static function sendHeaders( $status = 200, $altContentType = null )
  {
    switch( $status )
    {
      case 100:
        $msg = 'Continue';
        break;

      case 101:
        $msg = 'Switching Protocols';
        break;

      case 200:
        $msg = 'OK';
        break;

      case 201:
        $msg = 'Created';
        break;

      case 202:
        $msg = 'Accepted';
        break;

      case 203:
        $msg = 'Non-Authoritative Information';
        break;

      case 204:
        $msg = 'No Content';
        break;

      case 205:
        $msg = 'Reset Content';
        break;

      case 206:
        $msg = 'Partial Content';
        break;

      case 300:
        $msg = 'Multiple Choices';
        break;

      case 301:
        $msg = 'Moved Permanently';
        break;

      case 302:
        $msg = 'Found';
        break;

      case 303:
        $msg = 'See Other';
        break;

      case 304:
        $msg = 'Not Modified';
        break;

      case 307:
        $msg = 'Temporary Redirect';
        break;

      case 308:
        $msg = 'Permanent Redirect';
        break;

      case 400:
        $msg = 'Bad Request';
        break;

      case 401:
        $msg = 'Unauthorized';
        break;

      case 402:
        $msg = 'Payment Required';
        break;

      case 403:
        $msg = 'Forbidden';
        break;

      case 404:
        $msg = 'Not Found';
        break;

      case 405:
        $msg = 'Method Not Allowed';
        break;

      case 406:
        $msg = 'Not Acceptible';
        break;

      case 407:
        $msg = 'Proxy Authentication Required';
        break;

      case 408:
        $msg = 'Request Timeout';
        break;

      case 409:
        $msg = 'Conflict';
        break;

      case 410:
        $msg = 'Gone';
        break;

      case 411:
        $msg = 'Length Required';
        break;

      case 412:
        $msg = 'Precondition Failed';
        break;

      case 413:
        $msg = 'Content Too Large';
        break;

      case 414:
        $msg = 'URI Too Long';
        break;

      case 415:
        $msg = 'Unsupported Media Type';
        break;

      case 416:
        $msg = 'Range Not Satisfiable';
        break;

      case 417:
        $msg = 'Expectation Failed';
        break;

      case 421:
        $msg = 'Misdirected Request';
        break;

      case 422:
        $msg = 'Unproccesable Content';
        break;

      case 426:
        $msg = 'Upgrade Required';
        break;

      case 451:
        $msg = 'Unavailable For Legal Reasons';
        break;

      case 500:
      default:
        $status = 500;
        $msg = 'Internal Server Error';
        break;

      case 501:
        $msg = 'Not Implemented';
        break;

      case 502:
        $msg = 'Bad Gateway';
        break;

      case 503:
        $msg = 'Service Unavailable';
        break;

      case 504:
        $msg = 'Gateway Timeout';
        break;

      case 505:
        $msg = 'HTTP Version Not Supported';
        break;
    }

    header( $_SERVER['SERVER_PROTOCOL']. ' '. $status. ' '. $msg, $status );
    header( 'Content-Type: '. ($altContentType ? $altContentType : 'text/html; charset=utf-8') );
  }

  public static function redirect( $url, $statusCode = 302 )
  {
    header( "Location: $url", true, $statusCode );
  }
}
