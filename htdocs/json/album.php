<?

/**
* @file htdocs/json/album.php
* Handles Ajax updates for album views.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include "../../lib/init.php";

  list( $dummy, $albumId ) = explode( "_", $_GET['album'] );
  $current = $_GET['current'];
  $action = $_GET['action'];

  if ( $action == "navleft" )
    $id = Album::findPrevious( $albumId, $current );
  else
    $id = Album::findNext( $albumId, $current );

  $q = $db->buildQuery( "select title, storage_id from pictures where id=%d", $id );
  $o = $db->getSingle($q);

  $response = array();

  $response['id'] = $o ? $id : 0;
  $response['title'] = $o ? $o->title : null;
  $response['url'] = $o ? Storage::url($o->storage_id) : null;
  $response['prev'] = Album::findPrevious( $albumId, $id );
  $response['next'] = Album::findNext( $albumId, $id );

  echo json_encode($response);
?>
