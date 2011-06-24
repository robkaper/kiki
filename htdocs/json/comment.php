<?

/**
* @file htdocs/json/comment.php
* Handles Ajax updates of comment posts.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../../lib/init.php";

  if ($_POST)
  {
    $errors = Comments::save( $db, $user );
    
    if ( $_POST['json'] )
    {
      list( $dummy, $objectId, $last ) = explode( "_", $_POST['last'] );

      $response = array();
      $response['formId'] = $_POST['formId'];
      $response['objectId'] = $objectId;
      $response['comments'] = Comments::show( $db, $user, $objectId, $last );
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    header( 'Location: '. $_SERVER['HTTP_REFERER'], true, 301 );
  }
?>