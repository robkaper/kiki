<?

class Page
{
  private $title;

  public function __construct( $title = null, $tagLine = null )
  {
    $this->title = $title;
    $this->tagLine = $tagLine;
  }

  public function header()
  {
    $user = $GLOBALS['user'];

    echo "<!DOCTYPE html>\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    echo "<head>\n";
    echo "<meta charset=\"UTF-8\"/>\n"; // utf-8 ?
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\"/>\n";  
    echo Google::siteVerification();
?>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= Config::$kikiPrefix ?>/styles/default.css" title="Kiki Default" />
<!-- <link rel="alternate stylesheet" type="text/css" href="/styles/sint.css" title="Sint" /> -->
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<?= Boilerplate::jsonLoad(true); ?>';
boilerplates['jsonSave'] = '<?= Boilerplate::jsonSave(true); ?>';
var fbUser = '<?= $user->fbUser ? $user->fbUser->id : 0; ?>';
var twUser = '<?= $user->twUser ? $user->twUser->id : 0; ?>';
</script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery-ui-timepicker-addon.min.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery.placeholder.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/default.js"></script>
<?
  $title = $this->title;
  if ( $title )
    $title .= " - ";
  $title .= Config::$siteName;
  echo "<title>$title</title>\n";
  echo Google::analytics();
?>
</head>
<body>
<?
  echo "<header>\n";
  echo "<section id=\"title\">\n";
  // FIXME: make logo size configurable
  echo "<a href=\"/\"><img src=\"". Config::$headerLogo. "\" alt=\"". Config::$siteName. "\" style=\"width: 50px; height: 50px; float: left;\"/></a>\n";
//  echo "<div id=\"search\" style=\"float: right;\"><input type=\"text\"/></div>\n";
  echo "<h1><a href=\"/\">". Config::$siteName. "</a></h1>\n";
  echo $this->tagLine. "\n";
  echo "<br class=\"spacer\">\n";
  echo "</section>\n";
  echo "</header>\n";

  echo "<nav>\n";
  echo Boilerplate::navMenu( $user );
  echo "</nav>\n";
  echo "<nav class=\"second\">\n";
  echo Boilerplate::navMenu( $user, 2 );
  echo "</nav>\n";
?>

<div id="sw">
<div id="sidebar">
<?
  global $user, $anyUser, $allUsers;

  $me = new User(1);
  if ( $me->id )
  {
    list( $type, $name, $pic ) = $me->socialData();
    echo "<div class=\"box\">\n";
    echo Boilerplate::socialImage( $type, $name, $pic );
    echo "<p>Ik ben <b>$name</b>.</p><br class=\"spacer\"/>\n";
    echo "</div>\n";
  }

  $fbStyle = $user->fbUser ? "" : "display: none;";
  $twStyle = $user->twUser ? "" : "display: none;";
  $whoStyleOr = $anyUser ? "display: none;" : "";
  $whoStyleAnd = $allUsers ? "display: none;" : "";

  list( $type, $name, $pic ) = $user->socialData( 'facebook' );
  echo "<div class=\"box\" id=\"fbYouAre\" style=\"$fbStyle\">\n";
  echo Boilerplate::socialImage( 'facebook', $name, $pic, "fbImg" );
  echo "<p>Jij bent <b><span class=\"fbName\">$name</span></b>.</p><br class=\"spacer\"/>\n";
  echo "</div>\n";

  list( $type, $name, $pic ) = $user->socialData( 'twitter' );
  echo "<div class=\"box\" id=\"twYouAre\" style=\"$twStyle\">\n";
  echo Boilerplate::socialImage( 'twitter', $name, $pic, "twImg" );
  echo "<p>Jij bent <b><span class=\"twName\">$name</span></b>.</p><br class=\"spacer\"/>\n";
  echo "</div>\n";

  echo "<div class=\"box\" id=\"whoAreYou\" style=\"$whoStyleAnd\">\n";
  echo "<a name=\"login\"></a>\n";
  echo "<p class=\"youUnknown\" style=\"$whoStyleOr\">Mag ik ook weten wie jij bent?</p>\n";

  // FIXME: boilerplate this?
  if ( !$user->fbUser )
  {
    global $fb;
    $fbUrl = htmlspecialchars( $fb->getLoginUrl() );
    if ( $fbUrl )
      echo "<a id=\"fbLogin\" href=\"$fbUrl\" onclick=\"return fbLogin();\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/facebook_signin.png\" alt=\"Sign in with Facebook\"/></a>\n";
  }

  if ( !$user->twUser )
    echo "<a id=\"twLogin\" href=\"/kiki/twitter-redirect.php\" rel=\"nofollow\"><img src=\"". Config::$kikiPrefix. "/img/komodo/twitter_signin.png\" alt=\"Sign in with Twitter\"/></a>\n";

  echo "<p style=\"$whoStyleAnd\">(<a href=\"/proclaimer.php#privacy\">Privacybeleid</a>)</p>\n";

  if ( 0 && $user->isAdmin() )
    echo Google::adSense( "4246395131" );

  echo "</div>\n";
?>
</div>
</div>

<div id="cw">
<div id="content">
<?
    echo "<h1>$this->title</h1>\n";
  }
  
  public function footer()
  {
?>
</div>
</div>

<? if ( Config::$facebookApp ) { ?>
<div id="fb-root"></div>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
FB.init( {appId: '<?= Config::$facebookApp ?>', status: true, cookie: true, xfbml: true} );
FB.Event.subscribe( 'auth.sessionChange', function(response) { onFbResponse(response); } );
</script>
<? } // Config::$facebookApp
   if ( Config::$twitterApp && Config::$twitterAnywhere ) { ?>
<script src="http://platform.twitter.com/anywhere.js?id=<?= Config::$twitterApp ?>&v=1" type="text/javascript"></script>
<script>
twttr.anywhere( function (T) {
  T.bind("authComplete", function (e, user) { onTwLogin(e, user); } );
  T.bind("signOut", function (e) { onTwLogout(e); } );
  // TODO: twttr.anywhere.signOut();

  var twLogin = document.getElementById("twLogin");
  if ( twLogin )
  {
    twLogin.onclick = function () {
      T.signIn();
      return false;
    }
  }
} );
</script>
<? } // Config::$twitterApp ?>
<div id="fw">
<div class="footer"><?= Boilerplate::copyright(); ?></div>
</div>

</body>
</html>
<?
    Log::debug( "exit: ". $_SERVER['REQUEST_URI'] );
  }

}

?>