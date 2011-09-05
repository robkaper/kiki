<?

/**
* @file htdocs/rewrite/articles.php
* Controller for the Article system, assumes mod_rewrite rules pointing
* baseURI here and setting section and articleId already.
* @todo Move this into a proper controller system, where the main controller
* maps baseURI to the local controller, which handles the remainder of the
* URI.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../../lib/init.php";

  $section = $_SERVER['section'];
  $articleId = $_SERVER['articleId'];

  if ( $articleId )
    $title = Articles::title( $db, $user, $articleId );
  else
  {
    $q = $db->buildQuery( "select id,title from sections where base_uri='/%s/'", $section );
    $o = $db->getSingle($q);
    $title = $o ? $o->title : null;
  }

  $page = new Page( $title );
  $page->addStylesheet( Config::$kikiPrefix. "/scripts/prettify/prettify.css" );
  $page->header();

  if ( $articleId )
  {
    if ( $title )
      echo Articles::showSingle( $db, $user, $articleId );
    else
    {
      include $GLOBALS['kiki']. "/htdocs/404.php";
      exit();
    }
  }
  else
    echo Articles::showMulti( $db, $user, $o->id );

  $page->footer();
?>