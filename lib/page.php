<?

/**
* @file lib/page.php
* Provides the Page class.
* @class Page
* Creates a HTML page
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Page
{
  /**
  * @var string title of the page
  */
  private $title;

  /**
  * @var array available stylesheets
  */
  private $stylesheets;

  /**
  * @var string raw main content of the page, in HTML (excludes navigation, utilities, sidebars, headers/footers and everything else sufficiently templated)
  */
  private $content;

  /**
  * @var string tagline (subtitle) of the page
  */
  public $tagLine;

  /**
  * @var int HTTP status code
  */
  private $httpStatus = 200;

  /**
  * Initialises the page.
  * @param $title [string] (optional) nearly required, but probably best left empty for the index page of a site
  * @param $tagLine [string] (optional) tagline (subtitle) of the page
  * @todo Perhaps title should not be optional. It could be omited in a
  *   special template for the main page.
  */
  public function __construct( $title = null, $tagLine = null )
  {
    $this->title = $title;
    $this->stylesheets = array();
    $this->content = null;
    $this->tagLine = $tagLine;
  }

  /**
  * Sets the page title.
  */
  public function setTitle( $title )
  {
    $this->title = $title;
  }

  /** 
  * Appends a stylesheet.
  * @param $url [string] URL of the stylesheet
  */
  public function addStylesheet( $url )
  {
    $this->stylesheets[] = $url;
  }

  /**
  * Sets HTTP status code.
  * @param $httpStatus [int] HTTP status code
  */
  public function setHttpStatus( $httpStatus )
  {
    $this->httpStatus = $httpStatus;
  }

  /**
  * Sends the raw HTTP headers.
  * @todo Support all HTTP/1.1 codes from RFC2616 when sending the status
  *   code.  Currently only 200, 301, 302, 404 and 500 are supported. 
  *   Defaults to 200, but setting any unrecognised code in setHttpStatus()
  *   results in 500.
  */
  public function httpHeaders()
  {
    switch( $this->httpStatus )
    {
      case 200:
        header( $_SERVER['SERVER_PROTOCOL']. ' 200 OK', 200 );
        header( 'Content-Type: text/html; charset=utf-8' );
        break;
      case 301:
        header( $_SERVER['SERVER_PROTOCOL']. ' 301 Moved Permanently', 301 );
        break;
      case 302:
        header( $_SERVER['SERVER_PROTOCOL']. ' 302 Found', 302 );
        break;
      case 303:
        header( $_SERVER['SERVER_PROTOCOL']. ' 303 See Other', 303 );
        break;
      case 404:
        header( $_SERVER['SERVER_PROTOCOL']. ' 404 Not Found', 404 );
        header( 'Content-Type: text/html; charset=utf-8' );
        break;
      case 500:
      default:
        header( $_SERVER['SERVER_PROTOCOL']. ' 500 Internal Server Error', 500 );
        break;
    }
  }

  /**
  * Outputs the full HTML of the page. (Plus HTTP headers.)
  */
  public function html()
  {
    $user = $GLOBALS['user'];

    if ( Config::$customCss )
      $this->stylesheets[] = Config::$customCss;

    $title = $this->title;
    if ( $title )
      $title .= " - ";
    $title .= Config::$siteName;

    $this->httpHeaders();
    include Template::file('page/html');
  }

  /**
  * Allows setting $content with a custom script. End content with endContent().
  * @see header()
  */
  public function beginContent()
  {
    ob_start();
  }

  /**
  * Allows setting $content with a custom script. Requires beginContent()
  * prior to content generation.
  * @see footer()
  */
  public function endContent()
  {
    $this->content = ob_get_contents();
    ob_end_clean();
  }

  /**
  * Helper method for backwords compatibility. Allows the following usage:
  * @code
  * $page = new Page( 'title', 'tagLine' );
  * $page->header();
  * echo "<p>This is a page with content.</p>";
  * $page->footer();
  * @endcode
  *
  * I need to reconsider the full HTML template (page/html) because there is
  * a significant performance benefit in outputting headers prior to content
  * generation, which is (no longer) possible with the current approach.
  */
  public function header()
  {
    $this->beginContent();
  }
  
  /**
  * Helper method for backwords compatibility.
  * @see header()
  */
  public function footer()
  {
    $this->endContent();
    $this->html();
  }

}

?>