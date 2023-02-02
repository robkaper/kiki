<?php

/**
 * Returns updateable page parts in JSON format as required by jsonUpdate from htdocs/scripts/default.js
 *
 * @fixme This is quite a mess. Mostly because Boilerplate handling is
 * akward, but the entire idea of updating page parts and content by JSON
 * needs to be rethought.  It was originally intended for fluid
 * Javascript-based Facebook logins, but thosde are disabled pending a good
 * review of the multiple authentication methods.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  require_once "../../lib/init.php";

  $response = array();
  $response['content'] = array();

  $content = $_GET['content'];
  $ids = array();
  foreach( $content as $id )
  {
    $ids[] = "'". $db->escape($id). "'";

    $data = '';
    switch( $id )
    {
    case 'accountLink':
      $data = User::anyUser() ? Boilerplate::accountLink() : null;
      break;
    default:
      if ( preg_match( '/^commentFormWrapper_/', $id ) )
      {
        list( $dummy, $objectId ) = explode( '_', $id );
        $data = $user->id() ? Boilerplate::commentForm($user, $objectId) : Boilerplate::login();
      }
      else if ( preg_match( '/^navMenu-/', $id) )
      {
        list( $dummy, $level ) = explode( '-', $id );
        $data = Boilerplate::navMenu( $user, $level );
      }
      break;
    }

    $response['content'][] = array( 'id' => $id, 'html' => $data );
  }

  $qIds = join( $ids, ',' );
  $q = "select * from json_content where id in ($qIds)";
  // $response['q'] = $q;

  header( 'Content-type: application/json' );
  echo json_encode($response);
?>