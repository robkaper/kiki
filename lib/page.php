<?

/**
* @class Page
* Creates a HTML page
* @author Rob Kaper <http://robkaper.nl/>
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

  /** Appends a stylesheet.
  * @param $url [string] URL of the stylesheet
  */
  public function addStylesheet( $url )
  {
    $this->stylesheets[] = $url;
  }

  /**
  * Generates the full HTML of the page.
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