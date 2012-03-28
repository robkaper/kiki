<?

class Controller_Event extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $q = $db->buildQuery( "select id from events where cname='%s'", $this->objectId );
    $eventId = $db->getSingleValue($q);

    if ( $eventId )
    {
      $event = new Event( $eventId );
      if ( $event->id() )
      {
        $this->template = 'pages/event';
        $this->status = 200;
        $this->title = "Event: ". $event->title();
        $this->content = "event content: ". $event->content();
        
        return true;
      }
    }

    return false;
  }
}
  
?>