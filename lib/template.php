<?

/**
* @class Template
* Resolves template file locations to be included.  Does NOT offer any language constructs (PHP is a fine templating language itself).
* @todo Add variable assignments so that templaters do not need to rely on a
*   specific scope, or at least a way for templaters to easily see what
*   variables and objects are available in the present scope.
* @bug Does not offer any parsing (such as htmlspecialchars), which means
*   developers need to be careful about input that can inject unwanted
*   HTML/CSS leading to potential XSS vulnerabilities.
* @todo Support themes (/template/themename/etc)
* @author Rob Kaper <http://robkaper.nl/>
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
    // Try site specific template first
    $file = $GLOBALS['root']. '/templates/'. $template. '.tpl';
    if ( file_exists($file) )
      return $file;

    // Fallback to Kiki default template
    return $GLOBALS['kiki']. '/templates/'. $template. '.tpl';
  }
}

?>