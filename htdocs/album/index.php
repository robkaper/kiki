<?
  include_once "../../lib/init.php";

  // @fixme currently not in use, router.php doesn't support albums yet (not
  // a serious issue as albums weren't finished let alone integrated nyway)

  $page = new Page();
  $page->header();

  $albumId = isset($_SERVER['albumId']) ? $_SERVER['albumId'] : null;

  if ( $albumId )
  {
    $pictureId = isset($_SERVER['pictureId']) ? $_SERVER['pictureId'] : null;

    $album = new Album( $albumId );
    if ( $album->id )
    {
      $page->setTitle( "Album: ". $album->title. "/". $pictureId );
      $album->show( $pictureId );
    }
    else
    {
      $page->setTitle( "Album: Not found" );
      echo "<p>No such album.</p>\n";
    }
  }
  else
  {
    $page->setTitle( "Album: parameters missing" );
    echo "<p>No album specified.</p>\n";
  }

  $page->footer();
?>
