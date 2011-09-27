<?

class Controller_Page extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $this->title = Articles::title( $db, $user, $this->instanceId );

    // FIXME: Page doesn't exist yet at this moment, but we need to handle this..
    // $page->addStylesheet( Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    if ( $this->title )
    {
      $this->status = 200;
      $this->template = 'page/body';
      $this->content = Articles::showSingle( $db, $user, $this->instanceId );
    }
  }
}
  
?>