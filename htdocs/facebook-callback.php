<?
  include_once "../lib/init.php";

  if ( !$fbUser )
  {
    Log::error( "no fbUser in facebook-callback" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );

    // TODO: check if this is always automatically called for new permission
    // (read,publish,offline) grants or if we need to relay an extra
    // JSON call, to call FbUser::storePerm
    
    // TODO: Debug request conditions to identify others than
    // $_GET['session'] to include their detection in User::fbAuthenticate
    // itself (do we even need this now that we have json-update?)
    $user->fbUser->authenticate();
  }

  $response = array();
  echo json_encode( $response );
?>