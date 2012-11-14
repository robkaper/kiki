<?php

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

  static function login()
  {
    return "<p>\n<a href=\"#login\">Log in</a> via Facebook of Twitter om deze content te zien.</p>\n";
  }

  static function commentForm( $objectId )
  {
    $template = new Template( 'forms/comment' );
    $template->assign( 'objectId', $objectId );
    return $template->fetch();
  }

  public static function navMenuItem( &$user, $o )
  {
    if ( $o->admin && !$user->isAdmin() )
      return null;

    $o->icon = false;

    $match = preg_match( "#$o->url#", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null );
    $class = $o->class;
    $class .= ($match ? " active" : null);
    $class .= ($o->icon ? " icon" : null);
    $style = $o->icon ? " style=\"background-image: url(". Config::$iconPrefix. "/$o->icon);\"" : null;

    // return "<li class=\"$class\"${style}><a href=\"$o->url\">$o->title</a></li>\n";

    return array( 'url' => $o->url, 'title' => $o->title, 'style' => $style, 'class' => $class );
  }

  public static function navMenu( &$user, $level = 1 )
  {
    // FIXME: merge non-dynamic entries (such as functionality of /kiki/account/)
    // FIXME: add exact match boolean for url/context checking

    $menu = array();

    $context = null;

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
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
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and ('$qContext' like concat('%', context, '%') or context is null) order by sortorder asc";
    else
      $q = "select title, url, admin, class, icon from menu_items where level=$qLevel and context is null order by sortorder asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $o = $db->fetchObject($rs) )
        $menu[] = Boilerplate::navMenuItem( $user, $o );

    return $menu;
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

        $actions = array( 'publish_stream' => "schrijfrechten", 'user_events' => "user_events", 'create_event' => "create_event", 'read_stream' => "stream leesrechten", 'manage_pages' => "pagina (Page) beheerrechten" );
        foreach( $actions as $action => $desc )
        {
          $permission = $connection->hasPerm( $action );
          if ( $permission )
          {
            $permissionUrl = Config::$kikiPrefix. "/facebook-revoke.php?id=". $connection->id(). "&permission=$action";
            $content .= "<li>Deze site heeft $desc. (<a href=\"$permissionUrl\">Trek '$action' rechten in</a>).</li>\n";
          }
          else
          {
            $permissionUrl = Config::$kikiPrefix. "/facebook-grant.php?id=". $connection->id(). "&permission=$action";
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