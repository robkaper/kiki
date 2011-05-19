#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = $argv[1];
  include_once str_replace( "bin/mail-to-social.php", "lib/init.php", __FILE__ );

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

  // Retrieve security code. Doesn't consider invalid e-mail adresses, the
  // e-mail was delivered after all.
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

  if ( !$userId )
  {
    Log::debug( "invalid mailAuthToken: $mailAuthToken ($recipient)" );

    if ( $sender )
    {
      $from = Config::$siteName. " <no-reply@". $_SERVER['SERVER_NAME']. ">";
      $to = $sender;
      $subject = "Update error";
      
      $msg = "Your update request could not be processed. You e-mailed\n\n$recipient\n\nbut \"$mailAuthToken\" is not a valid authentication token.";
      $mailer = new Mailer( $from, $to, $subject, $msg );
      $mailer->send();
    }
    
    exit();
  }

  // Get structure
  $mp = mailparse_msg_parse_file( $tmpFile );
  $structure = mailparse_msg_get_structure($mp);
  // Log::debug( "structure: ". print_r( $structure, true ) );

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
        $id = Storage::save( $info['disposition-filename'], $contents );
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

  if ( !$subject && !$body && !count($attachments) )
  {
    Log::debug( "empty message" );

    if ( $sender )
    {
      $from = Config::$siteName. " <no-reply@". $_SERVER['SERVER_NAME']. ">";
      $to = $sender;
      $subject = "Update error";
      
      $msg = "Your update was not processed: you sent an empty message.";
      $mailer = new Mailer( $from, $to, $subject, $msg );
      $mailer->send();
    }
    exit();
  }

  $user->load( $userId );
  $user->authenticate();

  $albumUrl = null;
  if ( count($attachments) )
  {
    // Find album (and create if not exists)
    $album = Album::findByTitle('Mobile uploads', true );

    // TODO: check specifically for pictures, attachments could be other media type
    $pictures = $album->addPictures( $subject, $body, $attachments );

    $requestType = "album update";
    $albumUrl = SocialUpdate::postAlbumUpdate( $user, $album, $pictures );
  }
  else
  {
    $requestType = "status update";
    SocialUpdate::postStatus( $user, $body );
  }

  if ( $sender )
  {
    $from = Config::$siteName. " <no-reply@". $_SERVER['SERVER_NAME']. ">";
    $to = $sender;
    $subject = "Update successful";

    $msg = "Your $requestType was successful.\n\n";

    if ($albumUrl)
      $msg .= "Album URL:\n$albumUrl\n\n";

    if ( ($fbUrl=SocialUpdate::$fbRs->url) )
      $msg .= "Facebook URL:\n$fbUrl\n\n";

    if ( ($twUrl=SocialUpdate::$twRs->url) )
      $msg .= "Twitter URL:\n$twUrl\n\n";

    $mailer = new Mailer( $from, $to, $subject, $msg );
    $mailer->send();
  }

?>
