<?php

namespace Kiki;

use Kiki\MailerQueue;

require_once "Mail/RFC822.php";
require_once "Net/SMTP.php";

/**
 * Sends e-mails using Net_SMTP.
 *
 * @class Mailer
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Mailer
{
  /**
   * Send an e-mail. Adds it to the mail queue if enabled, otherwise directly calls the SMTP method.
   *
   * @param Email $email
   * @param int $priority
   * @return void
   */
  public static function send( &$email, $priority = 10 )
  {
    if ( Config::$mailerQueue )
      MailerQueue::store( $email, $priority );
    else
      self::smtp( $email );
  }

  /**
   * Sends and e-mail using Net_SMTP.
   *
   * @param Email $email
   * @return string Error message, or null upon success.
   */
  public static function smtp( &$email )
  {
/*
    if ( isset($GLOBALS['phpmail']) && $GLOBALS['phpmail'] )
    {
      $rfc = new \Mail_RFC822();
      $pureAddresses = $rfc->parseAddressList($email->to());
      foreach( $pureAddresses as $address )
      {
        $to = $address->mailbox. "@". $address->host;
        mail( $to, $email->subject(), $email->body(), $email->headers() );
      }
      return;
    }
*/

    Log::debug( "Mailer: subject:[". $email->subject(). "], from:[". $email->from(). "], to:". print_r($email->recipients(), true) );

    if ( !Config::$smtpHost )
    {
      $error = "Mailer: no SMTP host supplied";
      Log::debug($error);
      return $error;
    }
    
    if ( !($smtp = new \Net_SMTP(Config::$smtpHost, Config::$smtpPort, $_SERVER['SERVER_NAME'])) )
    {
      $error = "Mailer: unable to instantiate Net_SMTP object";
      Log::debug($error);
      return $error;
    }
    // $smtp->setDebug(true);

    $pear = new \PEAR();
    if ( $pear->isError($e = $smtp->connect()) )
    {
      $error = "Mailer: connect error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    if ( Config::$smtpUser && $pear->isError($e = $smtp->auth(Config::$smtpUser, Config::$smtpPass, Config::$smtpAuthType)) )
    {
      $error = "Mailer: authentication error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    $rfc = new \Mail_RFC822();
    $pureAddresses = $rfc->parseAddressList($email->from());
    $from = $pureAddresses[0]->mailbox. "@". $pureAddresses[0]->host;

    if ( $pear->isError($e = $smtp->mailFrom($from)))
    {
      $error = "Unable to set sender to [$from]: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    foreach( $email->recipients() as $toAddress )
    {
      $pureAddresses = $rfc->parseAddressList($toAddress);
      foreach( $pureAddresses as $address )
      {
        $to = $address->mailbox. "@". $address->host;

        if ( $pear->isError($e = $smtp->rcptTo($to)) )
        {
          $error = "Unable to set recipient to [$to]: ". $e->getMessage();
          Log::debug($error);
          return $error;
        }
      }
    }

    if ( $pear->isError($e = $smtp->data( $email->data() )) )
    {
      $error = "Unable to set data: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    Log::debug( "Mailer: sent: ". $smtp->_arguments[0] );

    $smtp->disconnect();

    return null;
  }
}

?>