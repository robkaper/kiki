<div id="sw"><aside>
<?
  global $user, $anyUser, $allUsers;

  $me = new User(1);
  if ( (Config::$facebookApp || Config::$twitterApp) && $me->id )
  {
    list( $type, $name, $pic ) = $me->socialData();
    echo "<div class=\"box\">\n";
    echo Boilerplate::socialImage( $type, $name, $pic );
    echo "<p>Ik ben <b>$name</b>.</p><br class=\"spacer\"/>\n";
    echo "</div>\n";
  }

  $fbStyle = $user->fbUser->authenticated ? "" : "display: none;";
  $twStyle = $user->twUser->authenticated ? "" : "display: none;";
  Log::debug( "anyUser: $anyUser, allUsers: $allUsers" );
  $whoStyleOr = $anyUser ? "display: none;" : "";
  $whoStyleAnd = $allUsers ? "display: none;" : "";

  if ( Config::$facebookApp )
  {
    list( $type, $name, $pic ) = $user->socialData( 'facebook' );
    echo "<div class=\"box\" id=\"fbYouAre\" style=\"$fbStyle\">\n";
    echo Boilerplate::socialImage( 'facebook', $name, $pic, "fbImg" );
    echo "<p>Jij bent <b><span class=\"fbName\">$name</span></b>.</p><br class=\"spacer\"/>\n";
    echo "</div>\n";
  }

  if ( Config::$twitterApp )
  {
    list( $type, $name, $pic ) = $user->socialData( 'twitter' );
    echo "<div class=\"box\" id=\"twYouAre\" style=\"$twStyle\">\n";
    echo Boilerplate::socialImage( 'twitter', $name, $pic, "twImg" );
    echo "<p>Jij bent <b><span class=\"twName\">$name</span></b>.</p><br class=\"spacer\"/>\n";
    echo "</div>\n";
  }

  if ( Config::$facebookApp || Config::$twitterApp )
  {
    echo "<div class=\"box\" id=\"whoAreYou\" style=\"$whoStyleAnd\">\n";
    echo "<p class=\"youUnknown\" style=\"$whoStyleOr\">Mag ik ook weten wie jij bent?</p>\n";

    // FIXME: boilerplate this?
    if ( !$user->fbUser->authenticated )
    {
      Log::debug( "fbUser not authenticated, need to show login button" );
      global $fb;
      if ( Config::$facebookApp && $fb )
      {
        Log::debug( "global fb exists" );
        // FIXME: rjkcust
        $fbUrl = htmlspecialchars( $fb->getLoginUrl( array( 'reg_perms' => 'publish_stream, offline_access', 'next' => 'http://robkaper.nl' ) ) );

        if ( $fbUrl )
        {
          Log::debug( "fb url not empty" );
          echo "<a id=\"fbLogin\" href=\"$fbUrl\" onclick=\"fbLogin();\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/facebook_signin.png\" alt=\"Sign in with Facebook\"/></a>\n";
        }
      }
    }

    if ( !$user->twUser->authenticated && Config::$twitterApp )
        echo "<a id=\"twLogin\" href=\"/kiki/twitter-redirect.php\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/twitter_signin.png\" alt=\"Sign in with Twitter\"/></a>\n";

    // FIXME: make conditional based on Config::privacyUrl or something similar, even though I
    // think every site should have a proclaimer and privacy policy...
    echo "<p style=\"$whoStyleAnd\">(<a href=\"/proclaimer.php#privacy\">Privacybeleid</a>)</p>\n";

    if ( 0 && $user->isAdmin() )
      echo Google::adSense( "4246395131" );

    echo "</div>\n";
  }
?>
</aside></div>