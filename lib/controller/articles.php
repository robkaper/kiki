<?

class Controller_Articles extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $template = Template::getInstance();
    $template->append( 'stylesheets', Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    if ( $this->objectId )
    {
      $article = new Article( 0, $this->objectId);
      if ( $article->id() && ( $article->visible() || $article->userId() == $user->id() ) )
      {
        $this->status = 200;

        $this->title = $article->title();

        $this->template = 'pages/default';

        // $template = new Template( 'content/articles-single' );
        // $template->assign( 'article', $article->data() );
        // $this->content = $template->fetch();
        $this->content = Articles::showSingle( $db, $user, $this->objectId );
      }
      else
      {
        Log::debug("article404");
        // TODO: set custom 404 template
        return false;
      }
    }
    else
    {
      $this->status = 200;

      $this->title = Articles::sectionTitle( $db, $user, $this->instanceId );

      $this->template = 'pages/default';

      $this->content = Articles::showMulti( $db, $user, $this->instanceId, 10, 2 );
    }
  }
}
  
?>