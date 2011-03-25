<?
  include_once "../lib/init.php";

  if ( !$fbUser )
  {
    Log::error( "no fbUser in facebook-revoke" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );
  }

  $permission = $_GET['permission'];
  Log::debug( "fbSession pre-revoke: ". print_r( $user->fbUser->fb->getSession(), true ) );
  $user->fbUser->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $permission ) );
  Log::debug( "fbSession post-revoke: ". print_r( $user->fbUser->fb->getSession(), true ) );

  $qUserId = $user->fbUser->id;
  $q = "update facebook_users set access_token=null where id=$qUserId";
  $db->query($q);

  header( "Location: ". $_SERVER['HTTP_REFERER'] );
  exit();

  // TODO: enable jsonUpdate in revoke callers
  $response = array();
  echo json_encode( $response );
?>