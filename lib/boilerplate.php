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

  static function commentForm( $objectId )
  {
    $template = new Template( 'forms/comment' );
    $template->assign( 'objectId', $objectId );
    return $template->fetch();
  }

}
