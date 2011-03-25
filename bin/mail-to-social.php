#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = $argv[1];
  $_SERVER['REQUEST_URI'] = "";
  include_once str_replace( "bin/mail-to-social.php", "lib/init.php", __FILE__ );

  // Temporarily store e-mail
  $data = file_get_contents("php://stdin");
  $tmpFile = tempnam( "/tmp", "kiki" );
  file_put_contents( $tmpFile, $data );

  // Parse headers for Subject
  list( $rawHeaders, $body ) = split( "(\r)?\n(\r)?\n", $data );
  $headers = array();
  preg_match_all('/([^: ]+): (.+?(?:(\r)?\n\s(?:.+?))*)(\r)?\n/m', "$rawHeaders\n", $headers );
  foreach( $headers[1] as $id => $key )
  {
    switch( strtolower(trim($key)) )
    {
    case 'subject':
      $subject = $headers[2][$id];
      Log::debug( "subject: $subject" );
      break;
    default:;
    }
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
        // FIXME: rjkcust
        $fileName = "/www/robkaper.nl/htdocs/upload/". $info['disposition-filename']; // "$partFile.data"
        file_put_contents( $fileName, $contents );
        $attachments[] = $info['disposition-filename'];
        Log::debug( "saved attachment: $fileName (". $info['content-type']. ")" );
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

  $fbMsg = $subject;
  // FIXME: rjkcust
  if ( count($attachments) )
  {
    $link = "http://robkaper.nl/upload/". $attachments[0];
    $picture = "http://robkaper.nl/upload/". $attachments[0];
    $tinyUrl = TinyUrl::get( $myUrl );
  }
  else
    exit();

  $name = '[no name]';
  $caption = '[no caption]';
  $description = '[no description]';

  $twMsg = $subject. " ". $tinyUrl;

  $user->load(1);
  $user->authenticate();

  $fbRs = Social::fbPublish( $fb, $fbMsg, $link, $name, $caption, $description, $picture );
  $twRs = Sociall:twPublish( $tw, $twMsg );
?>
