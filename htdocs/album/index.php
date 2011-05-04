<?
  include_once "../../lib/init.php";

  $page = new Page();
  $page->header();

  $albumId = isset($_SERVER['albumId']) ? $_SERVER['albumId'] : null;

  if ( $albumId )
  {
    $pictureId = isset($_SERVER['pictureId']) ? $_SERVER['pictureId'] : null;

    $album = new Album( $albumId );
    $album->show( $pictureId );
  }
  else
    echo "<p>No album specified.</p>\n";

  $page->footer();
?>
