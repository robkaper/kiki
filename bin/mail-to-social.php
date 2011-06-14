#!/usr/bin/php -q
<?

/**
* @file mail-to-social.php
* Reads an e-mail from STDIN and publishes the contents to social networks.
* Attached images are stored in a local album and a link to the album is
* published, not the image(s).
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  $_SERVER['SERVER_NAME'] = $argv[1];
  include_once str_replace( "bin/mail-to-social.php", "lib/init.php", __FILE__ );

  /**
  * Sends a report of the parsing and social update.
  * @param $to [string] e-mail address to send the report to
  * @param $errors [array] list of errors
  * @param $notices [array] list of notices
  * @todo Make template based (plain text and/or HTML using Mailer::setHtml()).
  * @todo This function should probably be in the SocialUpdate class, as well as the error/notice handling.
  */
  function sendReport( $to, &$errors, &$notices )
  {
    if (!$to)
      return;

    $errorCount = count($errors);      
    $from = Config::$siteName. " <no-reply@". $_SERVER['SERVER_NAME']. ">";
    $subject = $errorCount ? "Update error" : "Update successful";

    if ( $errorCount )
    {
      $msg = "Your update was not processed because of the following error(s):\n\n";
      foreach( $errors as $error )
        $msg .= "- $error\n\n";
    }
    else
    {
      if ( count($notices) )
      {
        $msg = "Your ". SocialUpdate::$type. " update was successful:\n\n";
        foreach( $notices as $notice )
          $msg .= "- $notice\n\n";
      }
      else
        $msg = "Your ". SocialUpdate::$type. " update was successful.";
    }

    $mailer = new Mailer( $from, $to, $subject, $msg );
    $mailer->send();
  }

  // Temporarily store e-mail
  $data = file_get_contents("php://stdin");
  $tmpFile = tempnam( "/tmp", "kiki" );
  file_put_contents( $tmpFile, $data );

  // Parse headers for Subject
  $subject = null;
  list( $rawHeaders, $body ) = split( "(\r)?\n(\r)?\n", $data );
  $headers = array();
  preg_match_all('/([^: ]+): (.+?(?:(\r)?\n\s(?:.+?))*)(\r)?\n/m', "$rawHeaders\n", $headers );
  foreach( $headers[1] as $id => $key )
  {
    switch( strtolower(trim($key)) )
    {
    case 'to':
      $recipient = iconv_mime_decode($headers[2][$id]);
      break;
    case 'from':
      $sender = iconv_mime_decode($headers[2][$id]);
      break;
    case 'subject':
      $subject = trim( iconv_mime_decode($headers[2][$id]) );
      break;
    default:;
    }
  }

  // Get structure
  $mp = mailparse_msg_parse_file( $tmpFile );
  $structure = mailparse_msg_get_structure($mp);
  // Log::debug( "structure: ". print_r( $structure, true ) );

  // Delete tmp file
  unlink( $tmpFile );

  // Retrieve security code. Doesn't consider invalid e-mail adresses, the e-mail was delivered after all.
  list( $localPart, $domain ) = explode( "@", $recipient );
  $mailAuthToken = null;
  if ( strstr($localPart, "+") )
    list( $target, $mailAuthToken ) = explode( "+", $localPart );

  // Get user
  $userId = 0;
  if ( $mailAuthToken )
  {
    $qToken = $db->escape( $mailAuthToken );
    $userId = $db->getSingleValue( "select id from users where mail_auth_token='$qToken'" );
  }

  Log::debug( "mailAuthToken: $mailAuthToken, userId: $userId" );

  $errors = array();
  $notices = array();

  if ( !$userId )
  {
    $errors[] = "You e-mailed <$recipient> but \"$mailAuthToken\" is not a valid authentication token.";
    sendReport( $sender, $errors, $notices );
    unlink($tmpFile);
    exit();
  }

  // Iterate structure
  $body = "";
  $attachments = array();
  foreach( $structure as $structurePart )
  {
    $partFile = "$tmpFile-$structurePart";

    $section = mailparse_msg_get_part($mp, $structurePart);
    $info = mailparse_msg_get_part_data($section);
    // Log::debug( "info: ". print_r( $info, true ) );

    if ( !in_array( $info['content-type'], array('multipart/mixed', 'multipart/alternative') ) )
    {
      // Get contents
      $part = mailparse_msg_get_part( $mp, $structurePart );
      ob_start();
      mailparse_msg_extract_part_file( $part, $tmpFile );
      $contents = ob_get_contents();
      ob_end_clean();

      if ( isset($info['disposition-filename']) )
      {
        // Attachment, store
        $name = iconv_mime_decode( $info['disposition-filename'] );
        $id = Storage::save( $name, $contents );
        $attachments[] = $id;
      }
      else if ( !$body && $info['content-type'] == 'text/plain' )
      {
        // Body part
        $body = trim( preg_replace( '/-- [\r\n]+.*/s', '', $contents ) );
      }
    }
  }

  // Validate picture attachments
  $pictureAttachments=array();
  foreach( $attachments as $storageId )
  {
    $finfo = @getimagesize( Storage::localFile($storageId) );
    if ( empty($finfo) && in_array( $pictureMimeTypes, $file_info['mime'] ) )
      $pictureAttachments[] = $storageId;
  }

  // No useable content
  if ( !$subject && !$body && !count($pictureAttachments) )
  {
    $errors[] = "You sent an empty message. (or only non-picture attachments)";
    sendReport( $sender, $errors, $notices );
    exit();
  }

  $user->load( $userId );
  $user->authenticate();

  $albumUrl = null;
  if ( count($pictureAttachments) )
  {
    // Find album (and create if not exists)
    $album = Album::findByTitle('Mobile uploads', true );

    $pictures = $album->addPictures( $subject, $body, $pictureAttachments );

    $requestType = "album update";
    $albumUrl = SocialUpdate::postAlbumUpdate( $user, $album, $pictures );
    $notices[] = "Album URL:\n$albumUrl";
  }
  else
  {
    $requestType = "status update";
    SocialUpdate::postStatus( $user, $body );
  }

  if ( ($fbUrl=SocialUpdate::$fbRs->url) )
    $notices[] = "Facebook URL:\n$fbUrl";

  if ( ($twUrl=SocialUpdate::$twRs->url) )
    $notices[] = "Twitter URL:\n$twUrl";

  sendReport( $sender, $errors, $notices );
?>
