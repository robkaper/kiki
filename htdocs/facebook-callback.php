<?

/**
* @file htdocs/facebook-callback.php
* Provides the callback URL required for Facebook authorisation.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../lib/init.php";

  if ( !$fbUser )
  {

    /// @todo Check if this is always automatically called for new
    /// permission (read,publish,offline) grants or if we need to relay an
    /// extra JSON call, to call FbUser::storePerm
    
    /// @todo Debug request conditions to identify others than
    /// $_GET['session'] to include their detection in
    /// FacebookUser::authenticate() itself.
    Log::error( "no fbUser in facebook-callback" );
    Log::debug( "request: ". print_r( $_REQUEST, true ) );
    $user->fbUser->authenticate();
  }

  $response = array();
  echo json_encode( $response );
?>