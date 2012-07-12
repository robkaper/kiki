<?

/**
 * Handles creation requests (POSTS) for comments. Returns all comments
 * inserted since the one specified by client as last known.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo verify backwards compatibility (should be a See Other redirect?)
 */

  // require_once "../lib/init.php";

  if ($_POST)
  {
    $errors = array();

    list( $dummy, $objectId, $last ) = explode( "_", $_POST['last'] );

    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";
    if ( !$body = $_POST['comment'] )
      $errors[] = "Je kunt geen leeg berichtje opslaan!";

    if ( !count($errors) )
    {
      $comment = new Comment();
      
      $comment->setInReplyToId( $objectId );
      $comment->setUserId( $user->id() );
      $comment->setBody( $body );
      $comment->setIpAddr( $_SERVER['REMOTE_ADDR'] );
      $comment->save();
    }
    
    if ( $_POST['json'] )
    {
      $response = array();
      $response['formId'] = $_POST['formId'];
      $response['objectId'] = $objectId;
      $response['comments'] = Comments::show( $db, $user, $objectId, $last );
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    Router::redirect( $_SERVER['HTTP_REFERER'], 303 );
  }
?>