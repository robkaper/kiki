<pre>
<?

/**
 * Revokes a Facebook permission and redirects to referer.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  require_once "../lib/init.php";

  if ( isset($_GET['id']) && isset($_GET['permission']) )
  {
    foreach( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Facebook' && $connection->id() == $_GET['id'] )
      {
        $connection->revokePerm( $_GET['permission'] );
      }
    }
  }

  Router::redirect( $_SERVER['HTTP_REFERER'] );
  exit();
?>