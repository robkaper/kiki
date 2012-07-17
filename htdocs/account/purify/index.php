<?
  $this->title = "Purify structure";

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $$this->template = 'pages/admin';

  ob_start();

  echo "<ul>";

  $articleAlbumIds = $db->getArray( "SELECT DISTINCT album_id AS id FROM articles" );

  $eventAlbumIds = $db->getArray( "SELECT DISTINCT album_id AS id FROM events" );

  $usedAlbumIds = array_merge( $articleAlbumIds, $eventAlbumIds );
  $qUsedAlbumIds = $db->implode( $usedAlbumIds );

  // Update title of albums linked to articles
  echo "<li>Update title of albums linked to articles... ";
  $q = "select a.id,ar.title from albums a, articles ar where a.id=ar.album_id";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $q = $db->buildQuery( "UPDATE albums set title='%s', system=true WHERE id=%d", $o->title, $o->id );
      $db->query($q);
    }
  }
  echo "done.</li>";

  // Update title of albums linked to events
  echo "<li>Update title of albums linked to events... ";
  $q = "select a.id,e.title from albums a, events e where a.id=e.album_id";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $q = $db->buildQuery( "UPDATE albums set title='%s', system=true WHERE id=%d", $o->title, $o->id );
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

  $q = "select header_image as id from articles";
  $articleImages = $db->getArray($q);
  $q = "select header_image as id from events";
  $eventImages = $db->getArray($q);

  // Delete orphaned pictures  
  echo "<li>Delete orphaned pictures... ";
  $q = "delete from pictures where id not in (select picture_id from album_pictures)";
  $rs = $db->query($q);
  echo "done.</li>";

  // Delete orphaned storage items
  echo "<li>Delete orphaned storage items... ";
  $q = "select id from storage where id not in (select storage_id from pictures)";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      if ( in_array($o->id, $articleImages) || in_array($o->id, $eventImages) )
        continue;

      $fileName = Storage::localFile( $o->id );
      unlink($fileName);
      $q = "delete from storage where id=". $o->id;
      $db->query($q);
    }
  }
  echo "done.</li>";

  $this->content = ob_get_clean();
?>