<?
  include_once "../lib/init.php";

  if ( !$fbUser )
  {
    Log::error( "no fbUser in facebook-callback" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );

    // TODO: Debug request conditions to identify others than
    // $_GET['session'] to include their detection in User::fbAuthenticate
    // itself (do we even need this now that we have json-update?)
    $user->fbUser->authenticate();
  }

  $response = array();
  echo json_encode( $response );
?>