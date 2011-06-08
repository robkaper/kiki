<?

class Page
{
  private $title;
  private $stylesheets;
  public $tagLine;

  public function __construct( $title = null, $tagLine = null )
  {
    $this->title = $title;
    $this->stylesheets = array();
    $this->tagLine = $tagLine;
  }

  public function addStylesheet( $url )
  {
    $this->stylesheets[] = $url;
  }

  public function header()
  {
    $user = $GLOBALS['user'];

    echo "<!DOCTYPE html>\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    echo "<head>\n";
    echo "<meta charset=\"UTF-8\"/>\n"; // utf-8 ?
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\"/>\n";
    Google::siteVerification();
?>
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?= Config::$kikiPrefix ?>/styles/default.css" title="Kiki Default" />
<?
    if ( Config::$customCss )
      $this->stylesheets[] = Config::$customCss;

    foreach( $this->stylesheets as $stylesheet )
      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$stylesheet\" />\n";
?>
<script type="text/javascript">
var boilerplates = new Array();
boilerplates['jsonLoad'] = '<?= Boilerplate::jsonLoad(true); ?>';
boilerplates['jsonSave'] = '<?= Boilerplate::jsonSave(true); ?>';
var fbUser = '<?= $user->fbUser->authenticated ? $user->fbUser->id : 0; ?>';
var twUser = '<?= $user->twUser->authenticated ? $user->twUser->id : 0; ?>';
var kikiPrefix = '<?= Config::$kikiPrefix; ?>';
var requestUri = '<?= $_SERVER['REQUEST_URI']; ?>';
</script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/jquery.placeholder.js"></script>
<script type="text/javascript" src="<?= Config::$kikiPrefix ?>/scripts/default.js"></script>
<?
  $title = $this->title;
  if ( $title )
    $title .= " - ";
  $title .= Config::$siteName;
  echo "<title>$title</title>\n";
  Google::analytics();
?>
</head>
<body>
<?
  include Template::file('header');
  include Template::file('nav');
  include Template::file('aside');
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

<?
    if ( Config::$facebookApp )
    {
      include Templates::file('facebook/connect');
    }

    if ( Config::$twitterApp && Config::$twitterAnywhere )
    {
      include Templates::file('twitter/anywhere');
    }
?>
<div id="fw">
<div class="footer"><?= Boilerplate::copyright(); ?></div>
</div>

<div id="jsonUpdate"></div>

</body>
</html>
<?
    Log::debug( "exit: ". $_SERVER['REQUEST_URI'] );
  }

}

?>