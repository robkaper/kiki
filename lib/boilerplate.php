<?

class Boilerplate
{
  public static function address()
  {
    return "<address>". nl2br( Config::$address ). "</address>\n";
  }

  public static function copyright()
  {
    return '<a href="/copyright.php">&copy; Copyright</a> '. Config::$copySince. '-'. date("Y"). ' '. Config::$copyOwner. '.';
  }

  public static function jsonLoad( $jsSafe = false )
  {
      return "<span class=\"jsonload\"><img src=\"". Config::$kikiPrefix. "/img/ajax-loader.gif\" alt=\"*\" /> Laden...</span>";
  }

  static function jsonSave( $jsSafe = false )
  {
    return "<span class=\"jsonload\"><img src=\"". Config::$kikiPrefix. "/img/ajax-loader.gif\" alt=\"*\" /> Opslaan...</span>";
  }

  static function login()
  {
    return "<p>\n<a href=\"#login\">Log in</a> via Facebook of Twitter om deze content te zien.</p>\n";
  }

  static function commentLogin()
  {
    return "<p>\n<a href=\"#login\">Log in</a> via Facebook of Twitter om te reageren.</p>\n";
  }

  static function socialImage( $type, $name, $pictureUrl, $extraClasses="", $extraStyles="" )
  {
    $img = $type ? "/img/komodo/${type}_16.png" : "/img/blank.gif";
    return "<img class=\"social $extraClasses\" style=\"background: url('$pictureUrl'); $extraStyles\" src=\"". Config::$kikiPrefix. "$img\" alt=\"$name\" />\n";
  }

  static function commentForm( &$user, $objectId )
  {
    if ( !$user )
      return null;

    list( $type, $name, $pic ) = $user->socialData();
    if ( !$type )
      return null;

    $content = "<div class=\"comment\" style=\"min-height: 0px;\">\n";
    $content .= Boilerplate::socialImage( $type, $name, $pic );
    $content .= "<div class=\"commentTxt\">\n";

    $content .= Form::open( null, Config::$kikiPrefix. "/json/comment.php", "POST" ); // PORT: name="form"
    $content .= Form::hidden( "objectId", $objectId ); // PORT: id=
    $content .= Form::textarea( "comment", null, null, "Schrijf een reactie..." ); // PORT: id=
    $content .= Form::button( "submit", "submit", "Plaats reactie" );
    $content .= Form::close();

    $content .= "</div>\n";
    $content .= "</div>\n";

    return $content;
  }

  public static function navMenuItem( $item )
  {
    $match = preg_match( "#$item[url]#", $_SERVER['REQUEST_URI'] );
    $class = (isset($item['class']) ? $item['class'] : ""). ($match ? " active" : "");
    return "<li class=\"$class\"><a href=\"$item[url]\">$item[title]</a></li>\n";
  }

  // FIXME: menu should be truly dynamic and managable and configurable outside of base classes, rjkcust
  public static function navMenu( &$user, $level = 1 )
  {
    $content = "";
    $context = null;

    $content .= "<ul id=\"navMenu-${level}\" class=\"jsonupdate\">\n";    

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : $_SERVER['REQUEST_URI'];
    preg_match( '#(/(.*))/((.*)(\.php)?)#', $requestUri, $matches );
    if ( count($matches) )
    {
      $context = $matches[2];
      $active = $matches[4];
    }
    $paths = explode( "/", $context );

    // $content .= "<li>[$level][$context]</li>";

    $level1 = array();
    $level2 = array();
    $level3 = array();

    // Level 1

    $level1[] = array( 'title' => 'Bar Poker<br />(blog)', 'url' => '/barpoker/' );
    $level1[] = array( 'title' => 'Lowlands<br />(blog)', 'url' => '/lowlands/' );

    $level1[] = array( 'title' => 'Web<br />development', 'url' => '/webdev/' );
    $level1[] = array( 'title' => 'Contact<br />en adres', 'url' => '/contact/', 'class' => 'right' );

    if ( $user->isAdmin() )
      $level1[] = array( 'title' => 'Admin<br />spul', 'url' => '/admin/', 'class' => 'right' );

    // Level 2 (context-based)
    if ( isset($paths[0]) )
    {
      switch( $paths[0] )
      {
      case 'webdev':
        $level2[] = array( 'url' => "/webdev/kiki/", 'title' => "Kiki" );
        break;
      default:;
      }
    }

    // Level 3 (even more so)
    if ( isset($paths[1]) )
    {
      switch( $paths[1] )
      {
        // Build level3 (/webdev/kiki/todo.php)
      }
    }

    $levelArray = "level$level";
    foreach( $$levelArray as $menuItem )
      $content .= Boilerplate::navMenuItem($menuItem);

    $content .= "</ul>\n";
    return $content;
  }
}

?>