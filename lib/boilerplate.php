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

namespace Kiki;

class Boilerplate
{
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

		// Replace with directmatch property.
		if ( $o->level == 1 )
	    $match = preg_match( "#$o->url#", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null );
		else
	    $match = preg_match( "#$o->url$#", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null );

		// Log::debug( "match #$o->url# with ". $_SERVER['REQUEST_URI']. " is ". (int)$match );
    $class = $o->class;
    $class .= ($match ? " active" : null);
    $class .= ($o->icon ? " icon" : null);
    $style = null;

    // return "<li class=\"$class\"${style}><a href=\"$o->url\">$o->title</a></li>\n";

    return array( 'url' => $o->url, 'title' => $o->title, 'style' => $style, 'class' => $class );
  }

  public static function navMenu( &$user, $level = 1 )
  {
    // FIXME: add exact match boolean for url/context checking

    $menu = array();

    $context = null;

    $matches = array();
    $requestUri = isset($_GET['uri']) ? $_GET['uri'] : isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    preg_match( '#(/(.*))/((.*)(\.php)?)#', $requestUri, $matches );
    
    if ( count($matches) )
      $context = $matches[2];

    $db = Core::getDb();

    $qLevel = $db->escape( $level );
    $qContext = $db->escape( $context );

    if ( $context )
      $q = "select title, url, level, admin, class, icon from menu_items where level=$qLevel and ('$qContext' like concat('%', context, '%') or context is null) order by sortorder asc";
    else
      $q = "select title, url, level, admin, class, icon from menu_items where level=$qLevel and context is null order by sortorder asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $o = $db->fetchObject($rs) )
			{
				$menuItem = Boilerplate::navMenuItem( $user, $o );
				if ( $menuItem )
					$menu[] = $menuItem;
			}

    return $menu;
  }

}
