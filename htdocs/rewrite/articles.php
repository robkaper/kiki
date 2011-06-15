<?
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
    echo Articles::showSingle( $db, $user, $articleId );
  else
    echo Articles::showMulti( $db, $user, $o->id );

  $page->footer();
?>