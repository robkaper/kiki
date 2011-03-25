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
    return "<img class=\"social $extraClasses\" style=\"background: url('$pictureUrl'); $extraStyles\" src=\"". Config::$kikiPrefix. "$img\" alt=\"[$name]\" />\n";
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

  public static function navMenuItem( &$user, $o )
  {
    if ( $o->admin && !$user->isAdmin() )
      return null;

    $match = preg_match( "#$o->url#", $_SERVER['REQUEST_URI'] );
    $class = $o->class. ($match ? " active" : "");
    return "<li class=\"$class\"><a href=\"$o->url\">$o->title</a></li>\n";
  }

  public static function navMenu( &$user, $level = 1 )
  {
    $context = null;

    $content = "<ul id=\"navMenu-${level}\" class=\"jsonupdate\">\n";    

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : $_SERVER['REQUEST_URI'];
    preg_match( '#(/(.*))/((.*)(\.php)?)#', $requestUri, $matches );
    if ( count($matches) )
    {
      $context = $matches[2];
      $active = $matches[4];
    }
    $paths = explode( "/", $context );

    $db = $GLOBALS['db'];

    $qLevel = $db->escape( $level );
    $qContext = $db->escape( $context );

    $q = "select title, url, admin, class from menu_items where level=$qLevel and (context='$qContext' or context is null) order by sortorder asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $o = $db->fetchObject($rs) )
        $content .= Boilerplate::navMenuItem( $user, $o );

    $content .= "</ul>\n";
    return $content;
  }

  public static function facebookPermissions( &$user )
  {
    $content = "";

    if ( $user->fbUser->authenticated )
    {
      $content .= "<p>Je bent ingelogd als <strong>". $user->fbUser->name. "</strong>.</p>";

      $actions = array( 'publish_stream' => "schrijfrechten", 'offline_access' => "offline toegang" );
      foreach( $actions as $action => $desc )
      {
        $permission = $user->fbUser->fb->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $action ) );
        if ( $permission )
        {
          $permissionUrl = "/kiki/facebook-revoke.php?permission=$action";
          $content .= "<p>Deze site heeft $desc. (<a href=\"$permissionUrl\">Trek '$action' rechten in</a>).</p>\n";
        }
        else
        {
          $permissionUrl = $user->fbUser->fb->getLoginUrl( $params = array( 'req_perms' => $action ) );
          $content .= "<p>Deze site heeft geen $desc. (<a href=\"$permissionUrl\">Voeg '$action' rechten toe</a>).</p>\n";
        }
      }
    }
    else
      $content .= "<p>Je bent niet ingelogd.</p>\n";

    return $content;
  }

}

?>