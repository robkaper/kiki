<?

/**
* @file htdocs/facebook-revoke.php
* Revokes a Facebook permission and redirects to the referer.
* @todo Make this work with jsonUpdate.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  include_once "../lib/init.php";

  $user->fbUser->revokePerm( $_GET['permission'] );

  header( "Location: ". $_SERVER['HTTP_REFERER'] );
  exit();

  /// @todo enable jsonUpdate in revoke callers
  $response = array();
  echo json_encode( $response );
?>