<?
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