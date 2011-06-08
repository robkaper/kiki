<?

class Page
{
  private $title;
  private $stylesheets;
  private $content;
  public $tagLine;

  public function __construct( $title = null, $tagLine = null )
  {
    $this->title = $title;
    $this->stylesheets = array();
    $this->content = null;
    $this->tagLine = $tagLine;
  }

  public function addStylesheet( $url )
  {
    $this->stylesheets[] = $url;
  }

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

  // Helper method for backwards compatibility
  public function beginContent()
  {
    ob_start();
  }

  public function endContent()
  {
    $this->content = ob_get_contents();
    ob_end_clean();
  }

  // Mainly an alias for backwards compatibility, although I need to
  // reconsider this approach as there is a significant benefit in the old
  // header/footer distinction which was able to output the headers prior to
  // content generation
  public function header()
  {
    $this->beginContent();
  }
  
  // Mainly an alias for backwards compatibility, see header()
  public function footer()
  {
    $this->endContent();
    $this->html();
  }

}

?>