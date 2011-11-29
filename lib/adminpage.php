<?

/**
 * Creates an HTML page with administration menu. Embedded check for administation privileges.
 *
 * @class AdminPage
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class AdminPage extends AccountPage
{
  public function __construct( $title = null, $tagLine = null, $description = null )
  {
    parent::__construct( $title, $tagLine, $description );

    $user = $GLOBALS['user'];
    if ( !$user->isAdmin() )
    {
      echo "<p>\nYou do not have administration privileges.</p>\n";
      $page->footer();
      exit();
    }
  }
}

?>