<?php

/**
 * Class providing the Event object.
 *
 * Events are time-based entries.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2012 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Event extends Object
{
  private $start = null;
  private $end = null;
  // private $title = null;
  private $cname = null;
  private $location = null;
  private $description = null;
  private $featured = null;
  private $hashtags = null;
  private $albumId = 0;
  
  public function reset()
  {
    parent::reset();

    $this->start = null;
    $this->end = null;
    $this->title = null;
    $this->cname = null;
    $this->location = null;
    $this->description = null;
    $this->featured = false;
    $this->hashtags = null;
    $this->albumId = 0;
  }

  public function load()
  {
    $qFields = "id, o.object_id, o.ctime, o.mtime, start, end, o.user_id, title, cname, description, location, featured, o.visible, hashtags, album_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM events e LEFT JOIN objects o on o.object_id=e.object_id where e.id=%d or o.object_id=%d", $this->id, $this->objectId );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->start = $o->start;
    $this->end = $o->end;
    $this->title = $o->title;
    $this->cname = $o->cname;
    $this->description = $o->description;
    $this->location = $o->location;
    $this->featured = $o->featured;
    $this->hashtags = $o->hashtags;
    $this->albumId = $o->album_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    if ( !$this->cname || !$this->visible )
      $this->cname = Misc::uriSafe($this->title);

    $q = $this->db->buildQuery(
      "UPDATE events SET object_id=%d, start='%s', end='%s', title='%s', cname='%s', description='%s', location='%s', featured=%d, hashtags='%s', album_id=%d where id=%d",
      $this->objectId, $this->start, $this->end, $this->title, $this->cname, $this->description, $this->location, $this->featured, $this->hashtags, $this->albumId, $this->id
    );
    Log::debug($q);

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    if ( !$this->cname || !$this->visible )
      $this->cname = Misc::uriSafe($this->title);

    $this->ctime = date("Y-m-d H:i:s");

    $q = $this->db->buildQuery(
      "INSERT INTO events (object_id, start, end, title, cname, description, location, featured, hashtags, album_id) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', %d)",
      $this->objectId, $this->start, $this->end, $this->title, $this->cname, $this->description, $this->location, $this->featured, $this->hashtags, $this->albumId
    );
    Log::debug($q);

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function setStart( $start ) { $this->start = $start; }
  public function start() { return $this->start; }
  public function setEnd( $end ) { $this->end = $end; }
  public function end() { return $this->end; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setDescription( $description ) { $this->description = $description; }
  public function description() { return $this->description; }
  public function setLocation( $location ) { $this->location = $location; }
  public function location() { return $this->location; }
  public function setFeatured( $featured ) { $this->featured = $featured; }
  public function featured() { return $this->featured; }
  public function setHashtags( $hashtags ) { $this->hashtags = $hashtags; }
  public function hashtags() { return $this->hashtags; }
  public function setAlbumId( $albumId ) { $this->albumId = $albumId; }
  public function albumId() { return $this->albumId; }

  public function url()
  {
    $sectionBaseUri = "/kiki/event/";
    $urlPrefix = "http://". $_SERVER['SERVER_NAME'];
    $url = $urlPrefix. $sectionBaseUri. $this->cname;
    return $url;
  }

  /**
   * Creates an article edit form.
   *
   * @fixme Should be integrated into a template.
   *
   * @param User $user User object, used to show the proper connection links for publications.
   * @return string The form HTML.
   */
  public function form( &$user, $hidden=false )
  {
    $start = date( "d-m-Y H:i", $this->start ? strtotime($this->start) : time() );
    $end = date( "d-m-Y H:i", $this->end ? strtotime($this->end) : time() );
    $class = $hidden ? "hidden" : "";

    $content = Form::open( "eventForm_". $this->id, Config::$kikiPrefix. "/json/event.php", 'POST', $class, "multipart/form-data" );
    $content .= Form::hidden( "eventId", $this->id );
    $content .= Form::hidden( "albumId", $this->albumId );
    $content .= Form::text( "cname", $this->cname, "URL name" );
    $content .= Form::text( "title", $this->title, "Title" );
    $content .= Form::datetime( "start", $start, "Start" );
    $content .= Form::datetime( "end", $end, "End" );
    $content .= Form::textarea( "description", htmlspecialchars($this->description), "Description" );
    $content .= Form::text( "location", $this->location, "Location" );
    $content .= Form::checkbox( "featured", $this->featured, "Featured" );
    $content .= Form::checkbox( "visible", $this->visible, "Visible" );

    $this->loadPublications();

    $content .= "<label>Publications</label>";
    foreach( $this->publications as $publication )
    {
      $content .= "<a href=\"". $publication->url(). "\" class=\"button\"><span class=\"buttonImg ". $publication->service(). "\"></span>". $publication->service(). "</a>\n";
    }

    // TODO: Make this generic, difference with social update is the check
    // against an already stored external URL.
    foreach ( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Facebook' )
      {
        // TODO: inform user that, and why, these are required (offline
        // access is required because Kiki doesn't store or use the
        // short-lived login sessions)
        if ( !$connection->hasPerm('publish_stream') )
         continue;
        if ( !$connection->hasPerm('create_event') )
         continue;

        $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
      }
      else if (  $connection->serviceName() == 'Twitter' )
      {
        $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
        $content .= Form::text( "hashtags", $this->hashtags, "Hashtags" );
      }
    }

    $content .= Form::button( "submit", "submit", "Opslaan" );
    $content .= Form::close();

    return $content;
  }

  public function topImage()
  {
    // Provided for backwards compatibility.
    $q = $this->db->buildQuery( "SELECT storage_id AS id FROM album_pictures LEFT JOIN pictures ON pictures.id=album_pictures.picture_id WHERE album_id=%d ORDER BY album_pictures.sortorder ASC LIMIT 1", $this->albumId );
    return $this->db->getSingleValue( $q );
  }

  public function images()
  {
    // TODO: query album, do not do this here.
    $q = $this->db->buildQuery( "SELECT storage_id AS id FROM album_pictures LEFT JOIN pictures ON pictures.id=album_pictures.picture_id WHERE album_id=%d ORDER BY album_pictures.sortorder ASC", $this->albumId );
    return $this->db->getArray( $q );
  }

  public function content()
  {
    // rjkcust
    setlocale("LC_TIME", "nl_NL.utf8");
    $start = strftime( "%A %e %B %Y %R", strtotime($this->start) );
    $end = strftime( "%A %e %B %Y %R", strtotime($this->end) );

    $imgUrl = Storage::url( $this->topImage(), 320, 180, true );
    $content = "<img src=\"$imgUrl\" style=\"float: right; margin: 0 0 1em 1em;\">";

    $content .= Misc::markup( $this->description );

    $content .= "<ul>";
    $content .= "<li>Wanneer?<p class=\"small\">$start (over ". Misc::relativeTime($this->start). ")</p></li>";
    $content .= "<li>Waar?<p class=\"small\">". $this->location. "</p></li>";

    $this->loadPublications();
    foreach( $this->publications as $publication )
    {
      $content .= "<li><a href=\"". $publication->url(). "\" class=\"button\"><span class=\"buttonImg ". $publication->service(). "\"></span>". $publication->service(). "</a></li>\n";
    }

    $content .= "</ul>";

    return $content;
  }
}

?>