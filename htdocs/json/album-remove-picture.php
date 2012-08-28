<?php

  $albumId = $_POST['albumId'];
  $pictureId = $_POST['pictureId'];

  $response = array();

  $response['albumId'] = $albumId;
  $response['pictureId'] = $pictureId;

  $q = $db->buildQuery( "DELETE FROM album_pictures WHERE album_id=%d AND picture_id=%d", $albumId, $pictureId );
  $db->query($q);

  header( 'Content-type: application/json' );
  echo json_encode($response);
  exit();
