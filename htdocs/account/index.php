<?
  include_once "../../lib/init.php";

  $page = new Page( "Jouw Account" );
  $page->header();

  echo "<h2>Twitter</h2>\n";

  if ( $user->twUser->authenticated )
  {
    echo "<p>Je bent ingelogd als <strong>". $user->twUser->screenName. "</strong> (". $user->twUser->name. ").</p>\n";
    echo "<p>Deze site heeft schrijfrechten. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</p>\n";
    echo "<p>Deze site heeft offline toegang. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</p>\n";
  }
  else
    echo "<p>Je bent niet ingelogd.</p>\n";

  echo "<h2>Facebook</h2>\n";

  if ( $user->fbUser->authenticated )
  {
    echo "<p>Je bent ingelogd als <strong>". $user->fbUser->name. "</strong>.</p>";

    $actions = array( 'publish_stream' => "schrijfrechten", 'offline_access' => "offline toegang" );
    foreach( $actions as $action => $desc )
    {
      $permission = $user->fbUser->fb->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $action ) );
      if ( $permission )
      {
        $permissionUrl = $user->fbUser->fb->getLoginUrl( $params = array( 'req_perms' => $action ) );
        echo "<p>Deze site heeft $desc.</p>\n"; // (<a href=\"$permissionUrl\">Trek '$action' rechten in</a>).</p>\n";
        // TODO: revoke link.. using auth.revokeExtendedPermission, perm=action, uid=0, callback=0
      }
      else
      {
        $permissionUrl = $user->fbUser->fb->getLoginUrl( $params = array( 'req_perms' => $action ) );
        echo "<p>Deze site heeft geen $desc. (<a href=\"$permissionUrl\">Voeg '$action' rechten toe</a>).</p>\n";
      }
    }
  }
  else
    echo "<p>Je bent niet ingelogd.</p>\n";

  $page->footer();
?>
