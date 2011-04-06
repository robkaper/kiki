<?
  include "../../lib/init.php";

  $album = $_GET['album'];
  $current = $_GET['current'];
  $action = $_GET['action'];

  // FIXME: album and picture data model
  $qId = $db->escape( $current );
  if ( $action == "navleft" )
    $q = "select id, hash, extension from storage where id<$qId order by id desc limit 1";
  else if ( $action == "navright" )
    $q = "select id, hash, extension from storage where id>$qId order by id asc limit 1";

  $o = $db->getSingle($q);
  
  $response = array();
  $response['id'] = $o ? $o->id : 0;
  $response['url'] = $o ? Storage::url($o->id) : null;
  echo json_encode($response);
?>
