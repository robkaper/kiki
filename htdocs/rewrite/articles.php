<?
  include_once "../../lib/init.php";

  $section = $_SERVER['section'];
  $articleId = $_SERVER['articleId'];

  if ( $articleId )
    $title = Articles::title( $db, $user, $articleId );
  else
  {
    $qBaseUri = '/'. $db->escape($section). '/';
    $o = $db->getSingle( "select id,title from sections where base_uri='$qBaseUri'" );
    $title = $o->title;
  }

  $page = new Page( $title );
  $page->header();

  if ( $articleId )
    echo Articles::showSingle( $db, $user, $articleId );
  else
    echo Articles::showMulti( $db, $user, $o->id );

  $page->footer();
?>