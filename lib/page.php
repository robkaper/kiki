<?

class Page
{
  private $title;
  public $tagLine;

  public function __construct( $title = null, $tagLine = null )
  {
    $this->title = $title;
    $this->tagLine = $tagLine;
  }

  public function header()
  {
    $user = $GLOBALS['user'];
    $mvc = $GLOBALS['mvc'];

    echo "<!DOCTYPE html>\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    echo "<head>\n";
    echo "<meta charset=\"UTF-8\"/>\n"; // utf-8 ?
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\"/>\n";  
    echo Google::siteVerification();
?>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= Config::$kikiPrefix ?>/styles/default.css" title="Kiki Default" />
<?
    if ( Config::$customCss )
    {
      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"". Config::$customCss. "\" title=\"". Config::$siteName. "\" />\n";
    }
?>
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<?= Boilerplate::jsonLoad(true); ?>';
boilerplates['jsonSave'] = '<?= Boilerplate::jsonSave(true); ?>';
var fbUser = '<?= $user->fbUser ? $user->fbUser->id : 0; ?>';
var twUser = '<?= $user->twUser ? $user->twUser->id : 0; ?>';
var kikiPrefix = '<?= Config::$kikiPrefix; ?>';
var requestUri = '<? $_SERVER['REQUEST_URI']; ?>';
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
  $mvc->load( "header" );
  $mvc->load( "nav" );
  $mvc->load( "aside" );
?>
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