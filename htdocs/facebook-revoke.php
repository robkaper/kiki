<?

/**
 * Revokes a Facebook permission and redirects to referer.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  include_once "../lib/init.php";

  $user->fbUser->revokePerm( $_GET['permission'] );

  Router::redirect( $_SERVER['HTTP_REFERER'] );
  exit();
?>