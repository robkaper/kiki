<?php

	use Kiki\Core;

  $this->title = "Purify structure";

	$user = Core::getUser();
	$db = Core::getDb();

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $this->template = 'pages/admin';

  ob_start();

  echo "<ul>";

  // Delete generated thumbnails from cache
  $dir = Core::getRootPath(). "/storage/";
  echo "<li>Delete generated thumbnails from cache $dir ...";
  $deleteCount = 0;
  $deleteSize = 0;
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
  echo "done.<br>(files deleted: $deleteCount, bytes freed: $deleteSize)</li>";

  // Update title of albums linked to articles
  echo "<li>Update title of albums linked to articles... ";
  $updated = 0;
  $q = "select a.id,ar.title from albums a, articles ar where a.id=ar.album_id";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $q = $db->buildQuery( "UPDATE albums set title='%s', system=true WHERE id=%d", $o->title, $o->id );
      $rs2 = $db->query($q);
      $updated += $db->affectedRows($rs2);
    }
  }
  echo "done.<br>(albums updated: $updated)</li>";

  // Update title of albums linked to events
  echo "<li>Update title of albums linked to events... ";
  $updated = 0;
  $q = "select a.id,e.title from albums a, events e where a.id=e.album_id";
  $rs = $db->query($q);
  if ( $rs && $db->numRowS($rs) )
  {
    while( $o = $db->fetchObject($rs) )
    {
      $q = $db->buildQuery( "UPDATE albums set title='%s', system=true WHERE id=%d", $o->title, $o->id );
      $rs2 = $db->query($q);
      $updated += $db->affectedRows($rs2);
    }
  }
  echo "done.<br>(albums updated: $updated)</li>";

  // Delete all empty albums not linked to an article or event
  echo "<li>Delete all empty albums not linked to an article or event... ";
  $articleAlbumIds = $db->getObjectIds( "SELECT DISTINCT album_id AS id FROM articles" );
  $eventAlbumIds = $db->getObjectIds( "SELECT DISTINCT album_id AS id FROM events" );
  $usedAlbumIds = array_merge( $articleAlbumIds, $eventAlbumIds );
  $qUsedAlbumIds = $db->implode( $usedAlbumIds );
  $q = "delete from albums where id not in ($qUsedAlbumIds) and id not in (select album_id from album_pictures)";
  $rs = $db->query($q);
  $deleted = $db->affectedRows($rs);
  echo "done.<br>(albums deleted: $deleted)</li>";

	// Delete album/picture links for dereferenced albums
	echo "<li>Delete album/picture links for dereferenced albums... ";
  $q = "delete from album_pictures where album_id not in (select id from albums)";
	$rs = $db->query($q);
	$deleted = $db->affectedRows($rs);
	echo "done.<br>(links deleted: $deleted)</li>";

  // Delete orphaned pictures  
  echo "<li>Delete orphaned pictures... ";
  $q = "delete from pictures where id not in (select picture_id from album_pictures)";
  $rs = $db->query($q);
  $deleted = $db->affectedRows($rs);
  echo "done.<br>(pictures deleted: $deleted)</li>";

  // Delete orphaned storage items
  echo "<li>Delete orphaned storage items... ";
  $q = "delete from storage where id not in (select storage_id from pictures)";
  $rs = $db->query($q);
  $deleted = $db->affectedRows($rs);
  echo "done.<br>(items deleted: $deleted)</li>";
  
  // Delete orphaned storage files
  echo "<li>Delete all storage files not registered as storage item... ";
  $storageHashes = $db->getObjectIds( "SELECT DISTINCT hash AS id FROM storage" );
  $deleteCount = 0;
  $deleteSize = 0;
  $dir = Core::getRootPath(). "/storage/";
  $iterator = new DirectoryIterator($dir);
  foreach ($iterator as $fileinfo)
  {
    if ($fileinfo->isFile())
    {
      $fileName = $fileinfo->getFilename();
      $fileSize = filesize($dir. $fileName);

      list( $hash ) = explode( ".", $fileName );
      if  ( !in_array($hash, $storageHashes) )
      {
        unlink($dir. $fileName);
        $deleteSize += $fileSize;
        $deleteCount++;
      }
    }
  }
  echo "done.<br>(files deleted: $deleteCount, bytes freed: $deleteSize)</li>";

	// @todo: detect inconsistencies the other way around (storage items with
	// missing file, pictures with missing storage item, album/picture links
	// with missing picture).

  echo "<li>Delete orphaned objects... ";
  echo "<ul>";
  $totalDeleted = 0;
  $objectTables = array( 'users' => 'User', 'articles' => 'Article', 'publications' => 'SocialUpdate', 'comments' => 'Comment' );
  foreach( $objectTables as $table => $objectType )
  {
    echo "<li>$objectType... ";
    $q = "delete from objects where type='$objectType' and object_id not in (select object_id from $table)";
    $rs = $db->query($q);
    $deleted = $db->affectedRows($rs);
    echo "done.<br>($table deleted: $deleted)</li>";
    $totalDeleted += $deleted;
  }
  echo "</ul>";
  echo "done.<br>(total objects deleted: $totalDeleted)</li>";

  echo "</ul>";

  $this->content = ob_get_clean();
?>