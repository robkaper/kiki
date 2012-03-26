<?

/**
 * Creates an HTML page with administration menu (for administrators).
 *
 * @class AdminPage
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class AccountPage extends Page
{
  public function __construct( $title = null, $tagLine = null, $description = null )
  {
    parent::__construct( $title, $tagLine, $description );

    $user = $GLOBALS['user'];
    if ( $user->isAdmin() )
      $this->setBodyTemplate( 'pages/admin' );
  }
}

?>