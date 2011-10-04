<?

/**
 * @class I18n
 *
 * Utility class for internationalisation (i18n), assuming gettext.
 *
 * @fixme Add requirements check to status page.
 * @todo Check whether _() exists.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class I18n
{
  /**
   * Initialises gettext.
   */
  public static function init()
  {
    if ( !function_exists('bindtextdomain') || !function_exists('textdomain') )
      return;

    bindtextdomain('messages', $GLOBALS['kiki']. '/locale/');
    textdomain('messages');
  }

  /**
   * Sets the locale.
   *
   * @warning PHP locales and gettext are horribly specific about the exact
   * name which must precisely match an actually installed locale.  This
   * switch solves it for me, but it's equally horrible because all
   * variations of all supported languages must be hardcoded here.  Also,
   * while this works for me, there's no guarantee it will work for anyone
   * else and in fact will likely fail on a multitude of server configurations.
   *
   * @param string $locale The locale to use.
   */
  public static function setLocale( $locale )
  {
    switch( $locale )
    {
      case "nl":
        setlocale( LC_ALL, "nl", "nl_NL", "nl_NL.utf8", "nl_NL.UTF-8" );
        break;
      default:
        setlocale( LC_ALL, $locale );
        break;
    }
  }
}

?>