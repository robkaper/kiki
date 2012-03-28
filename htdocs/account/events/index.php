<?
  // TODO: finalise error handling for twitter post with insufficient permissions
  // TODO: add follow me/friend me buttons/links to external social sites
  // FIXME: make jsonable
  // TODO: error handling when message empty or no social network selected (requires: form validation)

  $page = new AccountPage( _("Events") );
  $page->header();

  if ( $_POST )
  {
    $picture = null;
    if ( isset($_FILES['picture']) )
    {
      $tmpFile = $_FILES['picture']['tmp_name'];
      $name = $_FILES['picture']['name'];
      $size = $_FILES['picture']['size'];

      $storageId = $tmpFile ? Storage::save( $name, file_get_contents($tmpFile) ) : 0;
      if ( $storageId )
      {
        echo $storageId. "/";
        $picture = Storage::localFile( $storageId );
        echo $picture. "/";
      }
    }

    $errors = array();

    if ( isset($_POST['title']) )
      $title = $_POST['title'];
    else
      $errors[] = "Geen titel ingevuld.";

    if ( isset($_POST['start']) )
      $start = strtotime($_POST['start']);
    else
      $errors[] = "Geen startdatum ingevuld.";

    $end = !empty($_POST['end']) ? strtotime($_POST['end']) : $start+3600;

    if ( isset($_POST['location']) )
      $location = $_POST['location'];
    else
      $errors[] = "Geen lokatie ingevuld.";

    $description = isset($_POST['description']) ? $_POST['description'] : null;

    if ( !count($errors) )
    {
      foreach( $_POST['connections'] as $id => $value )
      {
        if ( $value != 'on' )
          continue;

        $connection = $user->getConnection($id);
        if ( $connection )
        {
          $rs = $connection->createEvent( $title, $start, $end, $location, $description, $picture );
          if ( isset($rs['id']) )
          {
            $url = "https://www.facebook.com/events/". $rd['id']. "/";
            echo "<p>". $connection->serviceName(). " event aangemaakt: <a target=\"_blank\" href=\"". $url. "\">". $url. "</a></p>\n";
          }
          else if ( isset($rs->error) )
            echo "<p>\nEr is een fout opgetreden bij het aanmaken van je ". $connection->serviceName(). " event:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
          else
            echo "<p>\nEr is een fout opgetreden bij het aanmaken van je ". $connection->serviceName(). " event.</p>\n";
        }
      }
    }
    else
    {
      print_r( $errors );
    }
  }

  if ( isset($_GET['id']) )
  {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $event = new Event( $id );
    $album = new Album( $event->albumId() );

    // Create album for this event if it doesn't exist yet.
    if ( !$album->id() )
    {
      $album->save();
      $event->setAlbumId($album->id());
      if ( $event->id() )
        $event->save();
    }

    if ( $album->id() && $event->headerImage() )
    {
      // TODO: add header image to album
      $pictures = $album->addPictures( null, null, array($event->headerImage()) );
      Log::debug( "event had headerImage (". $event->headerImage(). ") that was not a picture in an album, added it to album ". $album->id() );
    }

    echo $event->form( $user );
    if ( $album->id() )
    {
      echo $album->form( $user );
    }
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
        echo "<pre>". print_r($o). "</pre>". PHP_EOL;

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

  $page->footer();
?>