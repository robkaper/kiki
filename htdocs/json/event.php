<?
/**
 * Handles creation and modification requests (POSTS) for events.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2012 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

//  require_once "../../lib/init.php";

  if ($_POST)
  {
    $event = new Event( $_POST['eventId'] );

    $errors = array();
    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";

    // In case of multiple authors who can proofread, amend: don't update unless empty.
    if ( !$event->userId() )
      $event->setUserId( $user->id() );

    list( $date, $time ) = explode( " ", $_POST['start'] );
    list( $day, $month, $year ) = explode( "-", $date );
    $start = "$year-$month-$day $time";
    $event->setStart( $start );

    list( $date, $time ) = explode( " ", $_POST['end'] );
    list( $day, $month, $year ) = explode( "-", $date );
    $end = "$year-$month-$day $time";
    $event->setEnd( $end );

    $event->setTitle( $_POST['title'] );
    $event->setDescription( $_POST['description'] );
    $event->setLocation( $_POST['location'] );

    if ( isset($_POST['headerImage']) )
      $event->setHeaderImage( $_POST['headerImage'] );

    $event->setFeatured( (isset($_POST['featured']) && $_POST['featured']=='on') ? 1 : 0 );
    $event->setVisible( (isset($_POST['visible']) && $_POST['visible']=='on') ? 1 : 0 );
    $event->setFacebookUrl( $_POST['facebookUrl'] );
    $event->setTwitterUrl( $_POST['twitterUrl'] );
    $event->setHashtags( isset($_POST['hashtags']) ? $_POST['hashtags'] : null );
    $event->setAlbumId( isset($_POST['albumId']) ? $_POST['albumId'] : null );
        
    if ( !$event->title() )
      $errors[] = "Je kunt geen event zonder titel opslaan!";

    Log::debug( $errors );
    if ( !sizeof($errors) )
    {
      // Save event prior to publishing to create cname. 
      $event->save();
      
      // Publish event.
      if ( isset($_POST['connections']) )
      {
        foreach( $_POST['connections'] as $id => $value )
        {
          if ( $value != 'on' )
            continue;

          $connection = $user->getConnection($id);
          if ( $connection )
          {
            $rs = $connection->postEvent( $event );
            if ( isset($rs->id) )
            {
              $errors[] = "<p>". $connection->serviceName(). " status geupdate: <a target=\"_blank\" href=\"". $rs->url. "\">". $rs->url. "</a></p>\n";
              if ( $connection->serviceName() == 'Facebook' )
                $event->setFacebookUrl( $rs->url );
              else if ( $connection->serviceName() == 'Twitter' )
                $event->setTwitterUrl( $rs->url );
            }
            else
              $errors[] = "<p>\nEr is een fout opgetreden bij het updaten van je ". $connection->serviceName(). " status:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
          }
        }

        // Save event again, to store connection URLs.        
        $event->save();

        // Update title of corresponding album
        $album = new Album( $event->albumId() );
        $album->setSystem(true);
        $album->setTitle( $event->title() );
        $album->save();
      }
    }
    
    if ( isset($_POST['json']) )
    {
      $response = array();
      $response['formId'] = $_POST['formId'];
      $response['eventId'] = $event->id();
      $response['event'] = Events::showSingle( $db, $user, $event->id(), true);
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    if ( !count($errors) )
    {
      Router::redirect( $_SERVER['HTTP_REFERER'], 303 );
      exit();
    }

    $page = new AdminPage();
    $page->header();
    echo "fouten bij opslaan:";
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    $page->footer();
  }
?>