<?
  $page = new AdminPage( "Cleanup" );
  $page->header();

  echo "<ul>";

  $articleAlbumIds = $db->getArray( "SELECT DISTINCT album_id AS id FROM articles" );

  $eventAlbumIds = $db->getArray( "SELECT DISTINCT album_id AS id FROM events" );

  $usedAlbumIds = array_merge( $articleAlbumIds, $eventAlbumIds );
  $qUsedAlbumIds = $db->implode( $usedAlbumIds );

  // Update title of albums linked to articles
  echo "<li>Update title of albums linked to articles... ";
  $q = "select a.id,ar.title, ar.id as article_id from albums a, articles ar where a.id=ar.album_id";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $q = $db->buildQuery( "UPDATE albums set title='article %d - %s' WHERE id=%d", $o->article_id, $o->title, $o->id );
      $db->query($q);
    }
  }
  echo "done.</li>";

  // Delete all empty albums not linked to an article or event
  echo "<li>Delete all empty albums not linked to an article or event... ";
  $q = "delete from albums where id not in ($qUsedAlbumIds) and id not in (select album_id from album_pictures)";
  $rs = $db->query($q);
  echo "done.</li>";

  // Delete generated thumbnails from cache
  echo "<li>Delete generated thumbnails from cache...";
  $deleteCount = 0;
  $deleteSize = 0;
  $dir = $GLOBALS['root']. "/storage/";
  $iterator = new DirectoryIterator($dir);
  foreach ($iterator as $fileinfo)
  {
    if ($fileinfo->isFile())
    {
      $fileName = $fileinfo->getFilename();
      if ( preg_match('/^([^\.]+)\.([^x]+)x([^\.]+)\.((c?))?/', $fileName) )
      {
        $fileSize = filesize($dir. $fileName);

        unlink($dir. $fileName);
        $deleteSize += $fileSize;
        $deleteCount++;
      }
    }
  }
  echo "done, $deleteCount files ($deleteSize bytes).</li>";

/*
  $headerImages = array();
  $q = "select header_image from articles";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $headerImages[] = $o->header_image;
    }
  }
  echo "<pre>". print_r($headerImages,true). "</pre>". PHP_EOL;
*/
  
  echo "<h2>Orphaned storage items (not stored as picture)</h2>\n";
  
  $q = "select id, hash,extension from storage where id not in (select storage_id from pictures)";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      if ( in_array($o->id,$headerImages) )
        continue;

//      echo "<pre>". print_r($o,true). "</pre>". PHP_EOL;
      echo "<img src=\"/storage/". $o->hash. ".100x100.c.". $o->extension. "\" style=\"width: 100px; height: 100px; float: left;\" />";
    }
    echo "<br style=\"clear: left;\" />";
  }

  echo "<h2>Orphaned pictures (not stored in any album)</h2>\n";
  
  $q = "select id,storage_id from pictures where id not in (select picture_id from album_pictures)";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $uri = Storage::uri( $o->storage_id );
      echo "<img src=\"$uri\" style=\"width: 100px; height: 100px; float: left;\" />";
    }
    echo "<br style=\"clear: left;\" />";
  }


  $page->footer();
?>