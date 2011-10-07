<?

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

require_once "Mail/RFC822.php";
require_once "Net/SMTP.php";

class Mailer
{
  /**
   * Send an e-mail. Adds it to the mail queue if enabled, otherwise directly uses SMTP.
   *
   * @param Email $email
   * @param int $priority
   */
  public static function send( &$email, $priority = 10 )
  {
    if ( Config::$mailerQueue )
      MailerQueue::store( $email, $priority );
    else
      self::smtp( $email );
  }

  public static function smtp( &$email )
  {
    Log::debug( "Mailer: subject:[". $email->subject(). "], from:[". $email->from(). "], to:". print_r($email->recipients(), true) );

    if ( !Config::$smtpHost && !Config::$smtpUser && !Config::$smtpPass )
    {
      $error = "Mailer: no SMTP credentials supplied";
      Log::debug($error);
      return $error;
    }
    
    if ( !($smtp = new Net_SMTP(Config::$smtpHost, Config::$smtpPort)) )
    {
      $error = "Mailer: unable to instantiate Net_SMTP object";
      Log::debug($error);
      return $error;
    }
    // $smtp->setDebug(true);

    if ( PEAR::isError($e = $smtp->connect()) )
    {
      $error = "Mailer: connect error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    if ( PEAR::isError($e = $smtp->auth(Config::$smtpUser, Config::$smtpPass, Config::$smtpAuthType)) )
    {
      $error = "Mailer: authentication error: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    $rfc = new Mail_RFC822();
    $pureAddresses = $rfc->parseAddressList($email->from());
    $from = $pureAddresses[0]->mailbox. "@". $pureAddresses[0]->host;

    if ( PEAR::isError($e = $smtp->mailFrom($from)))
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

        if ( PEAR::isError($e = $smtp->rcptTo($to)) )
        {
          $error = "Unable to set recipient to [$to]: ". $e->getMessage();
          Log::debug($error);
          return $error;
        }
      }
    }

    if ( PEAR::isError($e = $smtp->data( $email->data() )) )
    {
      $error = "Unable to set data: ". $e->getMessage();
      Log::debug($error);
      return $error;
    }

    Log::debug( "Mailer: sent: ". $smtp->_arguments[0] );

    $smtp->disconnect();
  }
}

?>