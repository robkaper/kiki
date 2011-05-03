<?
  include "../../lib/init.php";

  list( $dummy, $albumId ) = split( "_", $_GET['album'] );
  $current = $_GET['current'];
  $action = $_GET['action'];

  $qId = $db->escape( $current );
  if ( $action == "navleft" )
    $id = Album::findPrevious( $albumId, $current );
  else
    $id = Album::findNext( $albumId, $current );

  $q = "select title, storage_id from pictures where id=$id";
  $o = $db->getSingle($q);

  $response = array();

  $response['id'] = $o ? $id : 0;
  $response['title'] = $o ? $o->title : null;
  $response['url'] = $o ? Storage::url($o->storage_id) : null;
  $response['prev'] = Album::findPrevious( $albumId, $id );
  $response['next'] = Album::findNext( $albumId, $id );

  echo json_encode($response);
?>
