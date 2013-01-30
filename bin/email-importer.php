#!/usr/bin/php -q
<?php

/**
* @file email-importer.php
* Reads an e-mail from STDIN and publishes the contents to social networks.
* Attached images are stored in a local album and a link to the album is
* published, not the image(s).
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

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
        $msg = "Your update was successful:\n\n";
        foreach( $notices as $notice )
          $msg .= "- $notice\n\n";
      }
      else
        $msg = "Your update was successful.";
    }

    $email = new Email( $from, $to, $subject );
    $email->setPlain( $msg );
    $email->send();
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

  // Retrieve security code. Doesn't consider invalid e-mail adresses, the e-mail was delivered after all.
  list( $localPart, $domain ) = explode( "@", $recipient );
  $mailAuthToken = null;
  if ( strstr($localPart, "+") )
    list( $prefix, $mailAuthToken, $target ) = explode( "+", $localPart );

  // Get user
  $userId = 0;
  if ( $mailAuthToken )
  {
    $q = $db->buildQuery( "select id from users where mail_auth_token='%s'", $mailAuthToken );
    $userId = $db->getSingleValue($q);
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

    if ( !in_array( $info['content-type'], array('multipart/mixed', 'multipart/alternative') ) )
    {
      // Get contents
      $part = mailparse_msg_get_part( $mp, $structurePart );

      // Use NULL as callback to return as string and not to STDOUT.
      $contents = mailparse_msg_extract_part_file( $part, $tmpFile, NULL );

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

  // Delete tmp file
  unlink( $tmpFile );

  // Validate picture attachments
  $pictureAttachments=array();
  foreach( $attachments as $storageId )
  {
    $finfo = @getimagesize( Storage::localFile($storageId) );
    if ( !empty($finfo) )
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

	$album = null;
  $albumUrl = null;
  if ( count($pictureAttachments) )
  {
    if ( isset($target) && $user->isAdmin() )
    {
      $matches = array();
      if (preg_match('#^album_([0-9])$#', $target, $matches) )
        $album = new Album( $matches[1] );
    }

    if ( !$album )
    {
      // Find album (and create if not exists)
      $album = Album::findByTitle('Mobile uploads', true );
    }

    $pictures = $album->addPictures( $subject, $body, $pictureAttachments );

    $requestType = "album update";

    if ( !$target )
    {
			// Automatically post social updates for album Mobile Uploads
			// @todo: make configurable whether publication is desired or not (maybe album property?)
			// @todo: each blog should have it's own album, or socialupdate can/should include album/picture

			$connections = $user->connections();
			foreach( $connections as $connection )
			{
				$rs = $connection->postAlbum( $album, $pictures );
				$notices[] = print_r( $rs, true );
			}
    }

    $notices[] = "Album URL:". PHP_EOL. $album->url(). PHP_EOL;
		$notices[] = print_r( $pictures,true );

  }
  else if ( !isset($target) )
  {
		$requestType = "status update";
    SocialUpdate::postStatus( $user, $body );
  }

  sendReport( $sender, $errors, $notices );
?>
