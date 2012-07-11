<?

/**
 * Redirects a user to the Facebook auth dialog after clearing permissions
 * so they will be reverified upon next hasPerm() call.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2012 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  if ( isset($_GET['id']) && isset($_GET['permission']) )
  {
    foreach( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Facebook' && $connection->id() == $_GET['id'] )
      {
        $connection->clearPermissions();
        $permissionUrl = $connection->getLoginUrl( array( 'scope' => $_GET['permission'], 'redirect_uri' => $_SERVER['HTTP_REFERER'] ), true );
        Router::redirect( $permissionUrl );
        exit();
      }
    }
  }

  Router::redirect( $_SERVER['HTTP_REFERER'] );
  exit();
