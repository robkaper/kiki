<?

/**
* @file htdocs/json/update.php
* Handles updateable page snippets requested by jsonUpdate() from htdocs/scripts/default.js.
* @todo This is quite a mess (mostly because Boilerplate handling is awkward).
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../../lib/init.php";

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
    case 'address':
      $data = User::anyUser() ? Boilerplate::address() : Boilerplate::login();
      break;
    case 'facebookPermissions':
      $data = Boilerplate::facebookPermissions($user);
      break;
    default:
      if ( preg_match( '/^commentForm_/', $id ) )
      {
        list( $dummy, $objectId ) = explode( '_', $id );
        $data = $user->anyUser() ? Boilerplate::commentForm($user, $objectId) : Boilerplate::login();
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