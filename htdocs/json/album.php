<?php

/**
 * Handles Ajax album navigation.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  // include "../../lib/init.php";

  list( $dummy, $albumId ) = explode( "_", $_GET['album'] );
  $current = $_GET['current'];
  $action = $_GET['action'];

  if ( $action == "navleft" )
    $id = Album::findPrevious( $albumId, $current );
  else
    $id = Album::findNext( $albumId, $current );

  $q = $db->buildQuery( "select title, storage_id from pictures where id=%d", $id );
  $o = $db->getSingleObject($q);

  $response = array();

  $response['id'] = $o ? $id : 0;
  $response['title'] = $o ? $o->title : null;
  $response['url'] = $o ? Storage::url($o->storage_id) : null;
  $response['prev'] = Album::findPrevious( $albumId, $id );
  $response['next'] = Album::findNext( $albumId, $id );

  header( 'Content-type: application/json' );
  echo json_encode( $response );
  exit();
