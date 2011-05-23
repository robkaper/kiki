<?
  include_once "../../lib/init.php";

  // FIXME: error handling for facebook post with insufficient permissions
  // TODO: finalise error handling for twitter post with insufficient permissions
  // TODO: add follow me/friend me buttons/links to external social sites
  // FIXME: make jsonable
  // TODO: error handling when message empty or no social network selected (requires: form validation)
  // TODO: textarea with maximum number of characters (for twitter, max=140)

  $page = new Page( "Social updates" );
  $page->header();

  if ( $_POST )
  {
    if ( $msg = $_POST['msg'] )
    {
      if ( isset($_POST['postFb']) && $_POST['postFb'] == 'on' )
      {
        // TODO: use SocialUpdate::postStatus
        $fbRs = $user->fbUser->post( $msg );
        if ( isset($fbRs->id) )
          echo "<p>Facebook status geupdate: <a target=\"_blank\" href=\"". $fbRs->url. "\">". $fbRs->url. "</a></p>\n";
        else
        {
          echo "<p>\nEr is een fout opgetreden bij het updaten van je Facebook status:</p>\n<pre>". print_r( $fbRs->error, true ). "</pre>\n";
          $loginUrl = $user->fbUser->fb->getLoginUrl( $params = array( 'req_perms' => 'publish_stream' ) );   
          echo "<a href=\"$loginUrl\">Facebook login met publish_stream rechten</a>\n";
        }
      }
    
      if ( isset($_POST['postTw']) && $_POST['postTw'] == 'on' )
      {
        $twRs = $user->twUser->post( $msg );
        if ( isset($twRs->id) )
          echo "<p>Twitter status geupdate: <a target=\"_blank\" href=\"". $twRs->url. "\">". $twRs->url. "</a></p>\n";
        else if ( $twRs->error == 'Read-only application cannot POST' )
        {
          // FIXME: temporary, either make app RW from the start or have two apps (one RO, one RW)
          echo "<p>\nJe hebt deze site alleen leesrechten gegeven en geen schrijfrechten. Helaas laat Twitter je deze rechten niet eenvoudig uitbreiden, je moet hiervoor twee stappen ondernemen:</p>\n";
          echo "<ol>\n";
          echo "<li>Verwijder de toegang van <b>robkaper.nl</b> bij je <a target=\"_blank\" href=\"http://twitter.com/settings/connections\">Twitter connection settings</a> (<q>Revoke access</q>)</li>\n";
          echo "<li><a href=\"/twitter-redirect.php\">Log opnieuw in</a>. Twitter geeft deze site dan lees- en schrijfrechten.</li>\n";
          echo "</ol>\n";
        }
        else
          echo "<p>\nEr is een fout opgetreden bij het updaten van je Twitter status:</p>\n<pre>". print_r( $twRs, true ). print_r( $user->twUser, true). "</pre>\n";
      }
    }
    else
      echo "<p>\nJe kunt geen lege status versturen.</p>\n";
  }

  if ( User::anyUser() )
  {
    echo Form::open( "socialForm" );
    echo Form::textarea( "msg", null, "Message", "Waar denk je aan?", 140 );
    if ( $user->fbUser )
      echo Form::checkbox( "postFb", false, "Facebook", "Update Facebook status" );
    if ( $user->twUser )
      echo Form::checkbox( "postTw", false, "Twitter", "Update Twitter status" );
    echo Form::button( "submit", "submit", "Update status" );
  }
  else
    echo Boilerplate::login();

  $page->footer();
?>