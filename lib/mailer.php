<?php

namespace Kiki;

use Kiki\MailerQueue;

use \PHPMailer\PHPMailer\SMTP as SMTP;
use \PHPMailer\PHPMailer\PHPMailer as PHPMailer;
use \PHPMailer\PHPMailer\Exception as Exception;

require_once Core::getRootPath(). "/vendor/PHPMailer/src/Exception.php";
require_once Core::getRootPath(). "/vendor/PHPMailer/src/PHPMailer.php";
require_once Core::getRootPath(). "/vendor/PHPMailer/src/SMTP.php";

/**
 * Sends e-mails using PHPMailer.
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
   * Sends an e-mail using PHPMailer.
   *
   * @param Email $email
   * @return string Error message, or null upon success.
   */
  public static function smtp( &$email )
  {
    Log::debug( "Mailer: subject:[". $email->subject(). "], from:[". $email->from(). "], to:". print_r($email->recipients(), true) );

    if ( !Config::$smtpHost )
    {
      $error = "Mailer: no SMTP host supplied";
      Log::debug($error);
      return $error;
    }

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = Config::$smtpHost;
    $mail->SMTPAuth = true;
    $mail->Port = Config::$smtpPort;
    $mail->Username = Config::$smtpUser;
    $mail->Password = Config::$smtpPass;
    $mail->CharSet = "utf-8";
    $mail->XMailer = 'Kiki/1.0';

    $mail->setFrom( Config::$mailSender, Config::$mailSenderName );
    // $mail->addCC('cc1@example.com', 'Elena');
    // $mail->addBCC('bcc1@example.com', 'Alex');

    foreach( $email->recipients() as $toAddress )
    {
      $mail->addAddress( $toAddress );
    }

    $mail->Subject = $email->subject();

    if ( $email->html() )
      $mail->msgHTML( $email->html() );
    else
      $mail->Body = trim( $email->body() );

    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->SMTPDebug = false;

    if( $mail->send() )
      return null;

    return $mail->ErrorInfo;

    // $mail->addAttachment('path/to/invoice1.pdf', 'invoice1.pdf');
    // $mail->addStringAttachment($mysql_data, 'db_data.db'); 
    // $mail->addEmbeddedImage('path/to/image_file.jpg', 'image_cid');
  }
}
