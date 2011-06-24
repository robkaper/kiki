<?

/**
* @file htdocs/tinyurl-redirect.php
* Lookups TinyURL URIs and redirects to the long URL.
* @todo Deprecate this, there's a specific mod_rewrite rule now but this
*   could just as well be handled by controller.php
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../lib/init.php";

  $redirectUrl = TinyUrl::lookup62( substr( $_SERVER['REQUEST_URI'], 1 ) );

  if ( $redirectUrl )
  {
    header( "Location: $redirectUrl", true, 301 );
    exit();
  }
  
  $page = new Page( "TinyURL Not Found" );
  $page->header();
  echo "<p>\nSorry! Kiki couldn't find a destination for this tiny URL.</p>\n";
  $page->footer();
?>