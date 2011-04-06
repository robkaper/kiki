<?
  include_once "../../lib/init.php";

  $page = new Page();
  $page->header();

  Album::showAlbum( $db, 1 );

  $page->footer();
?>
