<?php

  $this->title = _("Events");

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $this->template = 'pages/admin';

  ob_start();

  if ( isset($_GET['id']) )
  {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $event = new Event( $id );
    $album = new Album( $event->albumId() );

    // Create album for this event if it doesn't exist yet.
    if ( !$album->id() )
    {
      $album->setSystem(true);
      $album->setTitle( $event->title() );
      $album->save();

      $event->setAlbumId($album->id());
      if ( $event->id() )
        $event->save();

      if ( $album->id() && $event->headerImage() )
      {
        $pictures = $album->addPictures( null, null, array($event->headerImage()) );
        Log::debug( "event had headerImage (". $event->headerImage(). ") that was not a picture in an album, added it to album ". $album->id() );
      }
    }

    echo $event->form( $user );
    if ( $album->id() )
      echo $album->form( $user );
  }
  else
  {
    echo "<table>\n";
    echo "<thead>\n";

    echo "<tr>\n"; 
    echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\" /></a></td>\n";
    echo "<td colspan=\"2\">". _("Create a new event"). "</td>\n";
    echo "</tr>\n";

    echo "<tr><th></th><th></th><th></th><th>Date</th><th>Title</th></tr>\n";

    echo "</thead>\n";
    echo "<tbody>\n";

    $q = "SELECT id from events ORDER BY start desc LIMIT 25";
    $rs = $db->query($q);
    if ( $db->numRows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $event = new Event( $o->id );
        $class = $event->visible() ? "" : "disabled";
        echo "<tr class=\"$class\">\n"; 
        echo "<td><a href=\"?id=". $event->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\" /></a></td>\n";
        echo "<td><a href=\"". $event->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\" /></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\" /></a></td>\n";
        echo "<td>". $event->start(). "</td>\n";
        echo "<td>". $event->title(). "</td>\n";
        echo "</tr>\n";
      }
    }
    echo "</tbody>\n";
    echo "</table>\n";
  }

  $this->content = ob_get_clean();
?>