<div id="sw"><aside>
<?
  // FIXME: some logic in a template is fine by me, but this is way too much

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
  $whoStyleOr = User::anyUser() ? "display: none;" : "";
  $whoStyleAnd = User::allUsers() ? "display: none;" : "";

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
      if ( Config::$facebookApp )
      {
        $fbUrl = htmlspecialchars( $user->fbUser->fb->getLoginUrl( array( 'req_perms' => '' ) ) );
        if ( $fbUrl )
        {
          echo "<a id=\"fbLogin\" href=\"$fbUrl\" onclick=\"return fbLogin();\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/facebook_signin.png\" alt=\"Sign in with Facebook\"/></a>\n";
        }
      }
    }

    if ( !$user->twUser->authenticated && Config::$twitterApp )
        echo "<a id=\"twLogin\" href=\"". Config::$kikiPrefix. "/twitter-redirect.php\" onclick=\"return twLogin();\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/twitter_signin.png\" alt=\"Sign in with Twitter\"/></a>\n";

    echo "</div>\n";
  }

  echo "<div class=\"box\">\n";

  echo "<span id=\"accountLink\" class=\"jsonupdate\">\n";
  if ( User::anyUser() )
    echo Boilerplate::accountLink();
  echo "</span>\n";

  // FIXME: make conditional based on Config::privacyUrl or something similar, even though I
  // think every site should have a proclaimer and privacy policy...
  echo "<p><a href=\"/proclaimer.php#privacy\">Privacybeleid</a></p>\n";

  if ( 0 && $user->isAdmin() )
    echo Google::adSense( "4246395131" );

  echo "</div>\n";
?>
</aside></div>