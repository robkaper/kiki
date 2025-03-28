<?php

/**
 * @class I18n
 *
 * Utility class for internationalisation (i18n), assuming gettext.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

// As translation calls exist in the code everywhere, provide a sprintf
// fallback when gettext() is not installed so that dependency remains
// optional.

namespace Kiki;

if ( !function_exists("_") )
{
	function _()
	{
		$args = func_get_args();
		$n = count($args);
		switch($n)
		{
			case 0:
				return null;
			case 1:
				return $args[0];
			default:
				$text = array_shift($args);
				return sprintf( $text, $args );
		}
	}
}

class I18n
{
  /**
   * Initialises gettext.
   */
  public static function init()
  {
    // Supporting charsets other than UTF-8 is not high on the list of priorities.
    mb_internal_encoding('utf8');

    if ( !function_exists('bindtextdomain') || !function_exists('textdomain') )
      return;

    bindtextdomain('messages', Core::getInstallPath(). '/locale/');
    textdomain('messages');
  }

  /**
   * Sets the locale.
   *
   * @warning PHP locales and gettext are horribly specific about the exact
   * name which must precisely match an actually installed locale.  This
   * switch solves it for me (as in I can use "nl" and the right locale for
   * all environments I work with are supported), but it's a horrible
   * because all variations of all supported languages are hardcoded here
   * and while this works for me, there's no guarantee it will work for
   * anyone else and in fact will likely fail on a multitude of server
   * configurations.
	 *
	 * Should probably be configurable in Config, to the means where the
	 * locales can be mapped to two-letter country codes (which seem
	 * preferable to use in URLs or other more visible parts over some of the
	 * more detailed strings I've come across in locales).
	 *
   * @param string $locale The locale to use.
   */
  public static function setLocale( $locale )
  {
    switch( $locale )
    {
      case "hu":
        setlocale( LC_ALL, "hu", "hu_HU", "hu_HU.utf8", "hu_HU.UTF-8" );
        break;

      case "nl":
        setlocale( LC_ALL, "nl", "nl_NL", "nl_NL.utf8", "nl_NL.UTF-8" );
        break;

      case "en":
        break;

      default:
        setlocale( LC_ALL, $locale );
        return false;
        break;
    }
    return true;
  }
}

?>