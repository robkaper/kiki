<?
  include_once "../lib/init.php";

  $user->fbUser->revoke( $_GET['permission'] );

  header( "Location: ". $_SERVER['HTTP_REFERER'] );
  exit();

  // TODO: enable jsonUpdate in revoke callers
  $response = array();
  echo json_encode( $response );
?>