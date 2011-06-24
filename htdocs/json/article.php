<?

/**
* @file htdocs/json/article.php
* Handles Ajax saves of Article forms.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/
  include_once "../../lib/init.php";

  if ($_POST)
  {
    $articleId = Articles::save( $db, $user );
    
    if ( isset($_POST['json']) )
    {
      $response = array();
      $response['formId'] = $_POST['formId'];
      $response['articleId'] = $articleId;
      $response['article'] = Articles::showSingle( $db, $user, $articleId, true);
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    header( 'Location: '. $_SERVER['HTTP_REFERER'], true, 301 );
  }
?>