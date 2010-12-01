<?
  include_once "../lib/init.php";

  $redirectUrl = TinyUrl::lookup62( substr( $_SERVER['REQUEST_URI'], 1 ) );

  if ( $redirectUrl )
  {
    header( "Location: $redirectUrl", true, 301 );
    exit();
  }
  
  // FIXME: write a nice "sorry, we didn't find the long URL for your tinyURL" message
  $page = new Page( "TinyURL Not Found" );
  $page->header();
  echo "<p>\nSorry!</p>\n";
  $page->footer();
?>