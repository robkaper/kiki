<?

class Controller_Page extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $article = new Article( $this->instanceId );
    $this->title = $article->title();

    // FIXME: Page doesn't exist yet at this moment, but we need to handle this..
    // $page->addStylesheet( Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    if ( $article->visible() )
    {
      $this->title = $article->title();
      $this->status = 200;
      $this->template = 'pages/default';
      $this->content = Articles::showSingle( $db, $user, $this->instanceId );
    }
  }
}
  
?>