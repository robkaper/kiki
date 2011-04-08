<?
  include_once "../lib/init.php";

  $redirectUrl = TinyUrl::lookup62( substr( $_SERVER['REQUEST_URI'], 1 ) );

  if ( $redirectUrl )
  {
    header( "Location: $redirectUrl", true, 301 );
    exit();
  }
  
  $page = new Page( "TinyURL Not Found" );
  $page->header();
  echo "<p>\nSorry! Kiki CMS couldn't find a destination for this tiny URL.</p>\n";
  $page->footer();
?>