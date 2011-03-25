<?
  include_once "../lib/init.php";

  if ( !$fbUser )
  {
    Log::error( "no fbUser in facebook-revoke" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );
  }

  $user->fbUser->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $_GET['permission'] ) );

  header( "Location: ". $_SERVER['HTTP_REFERER'] );
  exit();

  // TODO: enable jsonUpdate in revoke callers
  $response = array();
  echo json_encode( $response );
?>