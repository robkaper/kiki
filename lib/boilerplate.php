<?

/**
 * Boilerplates.
 *
 * Various methods providing boilerplate framework content such as login and account links.
 *
 * @todo Refactor this to use templates and (database) strings that can be
 * updated by JSON or translated by i18n in a generic fashion.  The static
 * methods served a purpose in the earliest revisions of Kiki, but are
 * obviously not suitable as a long term solution.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
*/

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

  static function accountLinks()
  {
    return self::accountLink(). self::logoutLink();
  }

  static function accountLink()
  {
    return "<p><a href=\"". Config::$kikiPrefix. "/account/\">Jouw Account</a></p>\n";
  }

  static function logoutLink()
  {
    
    return "<p><a href=\"". Config::$kikiPrefix. "/account/logout.php\">Logout</a></p>\n";
  }

  static function socialImage( $type, $name, $pictureUrl, $extraClasses="", $extraStyles="" )
  {
    $img = $type ? "/img/komodo/${type}_16.png" : "/img/blank.gif";
    return "<img class=\"social $extraClasses\" style=\"background-image: url('$pictureUrl'); $extraStyles\" src=\"". Config::$kikiPrefix. "$img\" alt=\"[$name]\" />\n";
  }

  static function commentForm( &$user, $objectId )
  {
    if ( !$user )
      return null;

    $name = $user->name();
    $pic = $user->picture();
    $type = $user->serviceName();

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
    $class = $o->class;
    $class .= ($match ? " active" : null);
    $class .= ($o->icon ? " icon" : null);
    $style = null;
    if ( $o->icon )
      $style = " style=\"background-image: url(". Config::$iconPrefix. "/$o->icon);\"";
    return "<li class=\"$class\"${style}><a href=\"$o->url\">$o->title</a></li>\n";
  }

  public static function navMenu( &$user, $level = 1 )
  {
    // FIXME: merge non-dynamic entries (such as functionality of /kiki/account/)
    // FIXME: add exact match boolean for url/context checking

    $context = null;

    $content = "<ul id=\"navMenu-${level}\" class=\"jsonupdate\">\n";    

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : $_SERVER['REQUEST_URI'];
    preg_match( '#(/(.*))/((.*)(\.php)?)#', $requestUri, $matches );
    $active = null;
    if ( count($matches) )
    {
      $context = $matches[2];
      $active = $matches[4];
    }
    $paths = explode( "/", $context );

    $db = $GLOBALS['db'];

    $qLevel = $db->escape( $level );
    $qContext = $db->escape( $context );

    if ( $context )
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and (context like '$qContext%' or context is null) order by sortorder asc";
    else
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and context is null order by sortorder asc";
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

    foreach( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Twitter' )
      {
        $content .="<h2>Twitter</h2>\n";

        $content .="<ul>\n";
        $content .="<li>Je bent ingelogd als <strong>". $connection->screenName(). "</strong> (". $connection->name(). ") (<a href=\"/proclaimer.php#disconnect\">Ontkoppelhulp</a>).</li>\n";
        $content .="<li>Deze site heeft schrijfrechten. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";
        $content .="<li>Deze site heeft offline toegang. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";

        $content .="</ul>\n";
      }
      else if ( $connection->serviceName() == 'Facebook' )
      {
        $content .= "<h2>Facebook</h2>\n";

        $content .= "<ul>\n";

        $content .= "<li>Je bent ingelogd als <strong>". $connection->name(). "</strong> (<a href=\"/proclaimer.php#disconnect\">Ontkoppelhulp</a>).</li>";

        $actions = array( 'publish_stream' => "schrijfrechten", 'offline_access' => "offline toegang", 'create_event' => "event rechten" );
        foreach( $actions as $action => $desc )
        {
          $permission = $connection->hasPerm( $action );
          if ( $permission )
          {
            $permissionUrl = Config::$kikiPrefix. "/facebook-revoke.php?permission=$action";
            $content .= "<li>Deze site heeft $desc. (<a href=\"$permissionUrl\">Trek '$action' rechten in</a>).</li>\n";
          }
          else
          {
            $permissionUrl = $connection->getLoginUrl( array( 'req_perms' => $action ) );
            $content .= "<li>Deze site heeft geen $desc. (<a href=\"$permissionUrl\">Voeg '$action' rechten toe</a>).</li>\n";
          }
        }

        $content .= "</ul>\n";

      }
    }

    return $content;
  }

}

?>