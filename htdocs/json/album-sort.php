<?php

  $albumId = $_POST['albumId'];
  $sortOrder = $_POST['sortOrder'];

  $response = array();
  $response['albumId'] = $albumId;

  $i = 0;
  foreach( $sortOrder as $pictureId )
  {
    $q = $db->buildQuery( "UPDATE album_pictures SET sortorder=%d WHERE album_id=%d AND picture_id=%d", ++$i, $albumId, $pictureId );
    $db->query($q);
  }

  $response['lastPicture'] = $pictureId;
  $response['i'] = $i;
  
  header( 'Content-type: application/json' );
  echo json_encode($response);
  exit();
