<?

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
  private $userId = null;
  private $start = null;
  private $end = null;
  private $title = null;
  private $cname = null;
  private $location = null;
  private $description = null;
  private $headerImage = null;
  private $featured = null;
  private $visible = false;
  private $facebookUrl = null;
  private $twitterUrl = null;
  private $hashtags = null;
  private $albumId = 0;
  
  public function reset()
  {
    parent::reset();

    $this->userId = null;
    $this->start = null;
    $this->end = null;
    $this->title = null;
    $this->cname = null;
    $this->location = null;
    $this->description = null;
    $this->headerImage = null;
    $this->featured = false;
    $this->visible = false;
    $this->facebookUrl = null;
    $this->twitterUrl = null;
    $this->hashtags = null;
    $this->albumId = 0;
  }

  public function load()
  {
    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    $qFields = "id, o.object_id, e.ctime, e.mtime, start, end, user_id, title, cname, description, location, header_image, featured, visible, facebook_url, twitter_url, hashtags, album_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM events e LEFT JOIN objects o on o.object_id=e.object_id where id=%d or o.object_id=%d", $this->id, $this->objectId );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->userId = $o->user_id;
    $this->start = $o->start;
    $this->end = $o->end;
    $this->title = $o->title;
    $this->cname = $o->cname;
    $this->description = $o->description;
    $this->location = $o->location;
    $this->body = $o->body;
    $this->headerImage = $o->header_image;
    $this->featured = $o->featured;
    $this->visible = $o->visible;
    $this->facebookUrl = $o->facebook_url;
    $this->twitterUrl = $o->twitter_url;
    $this->hashtags = $o->hashtags;
    $this->albumId = $o->album_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    if ( !$this->cname || !$this->visible )
      $this->cname = Misc::uriSafe($this->title);

    $q = $this->db->buildQuery(
      "UPDATE events SET object_id=%d, ctime='%s', mtime=now(), start='%s', end='%s', user_id=%d, title='%s', cname='%s', description='%s', location='%s', header_image=%d, featured=%d, visible=%d, facebook_url='%s', twitter_url='%s', hashtags='%s', album_id=%d where id=%d",
      $this->objectId, $this->ctime, $this->start, $this->end, $this->userId, $this->title, $this->cname, $this->description, $this->location, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->hashtags, $this->albumId, $this->id
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
      "INSERT INTO events (object_id, ctime, mtime, start, end, user_id, title, cname, description, location, header_image, featured, visible, facebook_url, twitter_url, hashtags, album_id) VALUES (%d, '%s', now(), '%s', '%s', %d, '%s', '%s', '%s', '%s', %d, %d, %d, '%s', '%s', '%s', %d)",
      $this->objectId, $this->ctime, $this->start, $this->end, $this->userId, $this->title, $this->cname, $this->description, $this->location, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->hashtags, $this->albumId
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
  public function setUserId( $userId ) { $this->userId = $userId; }
  public function userId() { return $this->userId; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setDescription( $description ) { $this->description = $description; }
  public function description() { return $this->description; }
  public function setLocation( $location ) { $this->location = $location; }
  public function location() { return $this->location; }
  public function setHeaderImage( $headerImage ) { $this->headerImage = $headerImage; }
  public function headerImage() { return $this->headerImage; }
  public function setFeatured( $featured ) { $this->featured = $featured; }
  public function featured() { return $this->featured; }
  public function setVisible( $visible ) { $this->visible = $visible; }
  public function visible() { return $this->visible; }
  public function setFacebookUrl( $facebookUrl ) { $this->facebookUrl = $facebookUrl; }
  public function facebookUrl() { return $this->facebookUrl; }
  public function setTwitterUrl( $twitterUrl ) { $this->twitterUrl = $twitterUrl; }
  public function twitterUrl() { return $this->twitterUrl; }
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
    $content .= Form::hidden( "twitterUrl", $this->twitterUrl );
    $content .= Form::hidden( "facebookUrl", $this->facebookUrl );
    $content .= Form::text( "cname", $this->cname, "URL name" );
    $content .= Form::text( "title", $this->title, "Title" );
    $content .= Form::datetime( "start", $start, "Start" );
    $content .= Form::datetime( "end", $end, "End" );
    $content .= Form::textarea( "description", htmlspecialchars($this->description), "Description" );
    $content .= Form::text( "location", $this->location, "Location" );
    $content .= Form::albumImage( "headerImage", "Header image", $this->albumId, $this->headerImage );
    $content .= Form::checkbox( "featured", $this->featured, "Featured" );
    $content .= Form::checkbox( "visible", $this->visible, "Visible" );

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

        if ( !$this->facebookUrl )
          $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
      }
      else if (  $connection->serviceName() == 'Twitter' )
      {
        if ( !$this->twitterUrl )
        {
          $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
          $content .= Form::text( "hashtags", $this->hashtags, "Hashtags" );
        }
      }
    }

    $content .= Form::button( "submit", "submit", "Opslaan" );
    $content .= Form::close();

    return $content;
  }

  public function content()
  {
    // rjkcust
    setlocale("LC_TIME", "nl_NL.utf8");
    $start = strftime( "%A %e %B %Y %R", strtotime($this->start) );
    $end = strftime( "%A %e %B %Y %R", strtotime($this->end) );

    $imgUrl = Storage::url( $this->headerImage, 320, 180, true );
    $content = "<img src=\"$imgUrl\" style=\"float: right; margin: 0 0 1em 1em;\" />";

    $content .= Misc::markup( $this->description );

    $content .= "<ul>";
    $content .= "<li>Wanneer?<p class=\"small\">$start (over ". Misc::relativeTime($this->start). ")</p></li>";
    $content .= "<li>Waar?<p class=\"small\">". $this->location. "</p></li>";

    if ( $this->facebookUrl )
      $content .= "<li>Wie?<p class=\"small\">". $this->facebookUrl. "</p></li>";

    $content .= "</ul>";

    return $content;
  }
}

?>