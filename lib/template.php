<?

/**
 * Rudimentary template class.
 *
 * Resolves template file locations to be included.  Does NOT offer any language constructs (PHP is a fine templating language itself).
 *
 * @todo Add variable assignments so that templaters do not need to rely on
 * a specific scope, or at least a way for templaters to easily see what
 * variables and objects are available in the present scope.
 *
 * @bug Does not offer any parsing (such as htmlspecialchars), which means
 * developers need to be careful about input that can inject unwanted
 * HTML/CSS leading to potential XSS vulnerabilities.  Perhaps htmlentities
 * is even better.
 *
 * @see http://stackoverflow.com/questions/3623236/htmlspecialchars-vs-htmlentities-when-concerned-with-xss/3623297#3623297
 *
 * @class Template
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Template
{
  /**
  * Resolves a template filename. Looks for a site-specific file first and falls back on the Kiki default.
  * @param $template [string] name of the template
  * @return string filename of the template
  */
  static function file( $template )
  {
    if ( Config::$template != 'default' )
    {
      // Try site-specific version of chosen template
      $file = $GLOBALS['root']. '/templates/'. Config::$template. '/'. $template. '.tpl';
      if ( file_exists($file) )
        return $file;

      // Try Kiki base version of chosen template
      $file = $GLOBALS['root']. '/templates/'. Config::$template. '/'. $template. '.tpl';
      if ( file_exists($file) )
        return $file;
    }

    // Try site-specific version of default template
    $file = $GLOBALS['root']. '/templates/default/'. $template. '.tpl';
    if ( file_exists($file) )
      return $file;

    // Fallback to Kiki base version default template
    return $GLOBALS['kiki']. '/templates/default/'. $template. '.tpl';
  }
}

?>