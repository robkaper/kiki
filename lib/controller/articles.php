<?

class Controller_Articles extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    if ( $this->objectId )
      $this->title = Articles::title( $db, $user, $this->objectId );
    else
      $this->title = Articles::sectionTitle( $db, $user, $this->instanceId );

    // FIXME: Page doesn't exist yet at this moment, but we need to handle this..
    // $page->addStylesheet( Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    if ( $this->objectId )
    {
      if ( $this->title )
      {
        $this->status = 200;
        $this->template = 'page/body';
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
      Log::debug( "showMulti ". $this->instanceId );
      $this->status = 200;
      $this->template = 'page/body';
      $this->content = Articles::showMulti( $db, $user, $this->instanceId, 2 );
    }
  }
}
  
?>