<?
  include_once "../lib/init.php";

  if ( !$fbUser )
  {
    Log::error( "no fbUser in facebook-revoke" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );
  }

  $permission = $_GET['permission'];
  $fbRs = $user->fbUser->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $permission ) );

  $qUserId = $user->fbUser->id;
  $q = "update facebook_users set access_token=null where id=$qUserId";
  $db->query($q);

  $cookieId = "fbs_". Config::$facebookApp;
  setcookie( $cookieId, "", time()-3600, "/", $_SERVER['SERVER_NAME'] );

  header( "Location: ". $_SERVER['HTTP_REFERER'] );
  exit();

  // TODO: enable jsonUpdate in revoke callers
  $response = array();
  echo json_encode( $response );
?>