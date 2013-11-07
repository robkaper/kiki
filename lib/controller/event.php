<?php

namespace Kiki\Controller;

use Kiki\Controller;

use Kiki\Core;
use Kiki\Event;

class Event extends Controller
{
  public function exec()
  {
    $db = Core::getDb();
    $user = Core::getUser();

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
        $this->content = $event->content();
        
        return true;
      }
    }

    return false;
  }
}
  
?>