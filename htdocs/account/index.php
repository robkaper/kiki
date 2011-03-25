<?
  include_once "../../lib/init.php";

  $page = new Page( "My Account" );
  $page->header();
?><pre><?

  echo "<h2>User</h2>\n";

  print_r( $user );

  echo "<h2>Twitter</h2>\n";

  if ( $user->twUser->id )
    echo "<p>Je bent herkend: ". $user->twUser->id. ".</p>";
  else
    echo "<p>Je bent niet herkend.</p>\n";

  if ( $user->twUser->authenticated )
    echo "<p>Je bent ingelogd: ". $user->twUser->screenName. "/". $user->twUser->name. ".</p>";
  else
    echo "<p>Je bent niet ingelogd.</p>\n";

  echo "<p>TODO: analyseer/test/remove/add schrijfrechten.</p>";

  echo "authorize: ". print_r( $user->twUser->tw->get( 'account/verify_credentials' ), true );
  echo "authorize: ". print_r( $user->twUser->tw->get( 'authorize' ), true );
  echo "authorize: ". print_r( $user->twUser->tw->get( 'oauth/authorize' ), true );
  echo "authenticate: ". print_r( $user->twUser->tw->get( 'authenticate' ), true );
  echo "authenticate: ". print_r( $user->twUser->tw->get( 'oauth/authenticate' ), true );

  echo "<h2>Facebook</h2>\n";

  if ( $user->fbUser->id )
    echo "<p>Je bent herkend: ". $user->fbUser->id. ".</p>";
  else
    echo "<p>Je bent niet herkend.</p>\n";

  if ( $user->fbUser->authenticated )
    echo "<p>Je bent ingelogd: ". $user->fbUser->name. ".</p>";
  else
    echo "<p>Je bent niet ingelogd.</p>\n";

  print_r( $user->fbUser->fb->api('/me') );

  if ( $user->fbUser->accessToken )
    echo "<p>Je hebt offline access gegeven. (TODO: test/remove)</p>";
  else
    echo "<p>Je bent geen offline access gegeven. (TODO: add)</p>\n";

  echo "<p>TODO: analyseer/test/remove/add schrijfrechten.</p>";
  
?></pre><?
  $page->footer();
?>
