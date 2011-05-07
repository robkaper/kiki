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
      $recipient = $headers[2][$id];
      break;
    case 'subject':
      $subject = iconv_mime_decode( $headers[2][$id] );
      break;
    default:;
    }
  }

  // Retrieve security code. Doesn't consider invalid e-mail adresses, the
  // e-mail was delivered after all.
  list( $localPart, $domain ) = explode( "@", $recipient );
  list( $target, $mailAuthToken ) = explode( "+", $localPart );

  $qToken = $db->escape( $mailAuthToken );
  $userId = $db->getSingleValue( "select id from users where mail_auth_token='$qToken'" );

  if ( !$userId )
  {
    // FIXME: error handling, perhaps send a reply
    Log::debug( "invalid mailAuthToken: $mailAuthToken ($recipient)" );
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
        $fileName = iconv_mime_decode( $info['disposition-filename'] );
        $id = Storage::save( $fileName, $contents );
        $attachments[] = $id;
      }
      else if ( !$body && $info['content-type'] == 'text/plain' )
      {
        // Body part
        $body = preg_replace( '/-- [\r\n]+.*/s', '', $contents );
      }
    }
  }

  // Delete tmp file
  unlink( $tmpFile );

  if ( !$subject && !$body && !count($attachments) )
  {
    // FIXME: error handling, perhaps send a reply
    exit();
  }

  $user->load( $userId );
  $user->authenticate();

  if ( count($attachments) )
  {
    // Find album (and create if not exists)
    $album = Album::findByTitle('Mobile uploads', true );
    
    // TODO: check specifically for pictures, attachments could be other media type
    $pictures = $album->addPictures( trim($subject), trim($body), $attachments );

    SocialUpdate::postAlbumUpdate( $user, $album, $pictures );
  }
  else
    SocialUpdate::postStatus( $user, $body );

  // FIXME: error handling, perhaps send a reply

?>
