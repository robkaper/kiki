<?

class Controller_Page extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $article = new Article( $this->instanceId );
    $this->title = $article->title();

    $template = Template::getInstance();
    $template->append( 'stylesheets', Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

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