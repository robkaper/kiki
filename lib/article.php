<?

/**
 * Class providing the Article object.
 *
 * Articles are blog posts, news items, etc.
 *
 * @todo decide how to merge/polymorph/integrate this with Pages/Posts which
 * are nearly identical.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Article extends Object
{
  private $ipAddr = null;
  private $sectionId = null;
  private $userId = null;
  private $title = null;
  private $cname = null;
  private $body = null;
  private $headerImage = null;
  private $featured = false;
  private $visible = false;
  private $facebookUrl = null;
  private $twitterUrl = null;
  private $hashtags = null;
  private $albumId = 0;
  
  public function reset()
  {
    parent::reset();

    $this->ipAddr = null;
    $this->sectionId = null;
    $this->userId = null;
    $this->title = null;
    $this->hashtags = null;
    $this->cname = null;
    $this->body = null;
    $this->headerImage = null;
    $this->featured = false;
    $this->visible = false;
    $this->facebookUrl = null;
    $this->twitterUrl = null;
    $this->albumId = 0;
  }

  public function load()
  {
    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    $qFields = "id, o.object_id, a.ctime, a.mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, facebook_url, twitter_url, hashtags, album_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM articles a LEFT JOIN objects o on o.object_id=a.object_id where id=%d or o.object_id=%d", $this->id, $this->objectId );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->ipAddr = $o->ip_addr;
    $this->sectionId = $o->section_id;
    $this->userId = $o->user_id;
    $this->title = $o->title;
    $this->cname = $o->cname;
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
      "UPDATE articles SET object_id=%d, ctime='%s', mtime=now(), ip_addr='%s', section_id=%d, user_id=%d, title='%s', cname='%s', body='%s', header_image=%d, featured=%d, visible=%d, facebook_url='%s', twitter_url='%s', hashtags='%s', album_id=%d where id=%d",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->hashtags, $this->albumId, $this->id
    );
    Log::debug($q);

    if ( !$this->sectionId )
      Router::storeBaseUri( $this->cname, 'page', $this->id );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    if ( !$this->cname || !$this->visible )
      $this->cname = Misc::uriSafe($this->title);
    if ( !$this->ctime )
      $this->ctime = date("Y-m-d H:i:s");

    $q = $this->db->buildQuery(
      "INSERT INTO articles (object_id, ctime, mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, facebook_url, twitter_url, hashtags, album_id) VALUES (%d, '%s', now(), '%s', %d, %d, '%s', '%s', '%s', %d, %d, %d, '%s', '%s', '%s', %d)",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->hashtags, $this->albumId
    );
    Log::debug($q);

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    if ( !$this->sectionId )
      Router::storeBaseUri( $this->cname, 'page', $this->id );
          
    return $this->id;
  }

  public function setIpAddr( $ipAddr ) { $this->ipAddr = $ipAddr; }
  public function ipAddr() { return $this->ipAddr; }
  public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  public function sectionId() { return $this->sectionId; }
  public function setUserId( $userId ) { $this->userId = $userId; }
  public function userId() { return $this->userId; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setBody( $body ) { $this->body = $body; }
  public function body() { return $this->body; }
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
    $sectionBaseUri = Router::getBaseUri( 'articles', $this->sectionId );
    if ( !$sectionBaseUri )
      $sectionBaseUri = "/";
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
    $date = date( "d-m-Y H:i", $this->ctime ? strtotime($this->ctime) : time() );
    $class = $hidden ? "hidden" : "";

    $sections = array();
    $db = $GLOBALS['db'];
    $q = "select id,title from sections order by title asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $oSection = $db->fetchObject($rs) )
        $sections[$oSection->id] = $oSection->title;

    $content = Form::open( "articleForm_". $this->id, Config::$kikiPrefix. "/json/article.php", 'POST', $class, "multipart/form-data" );
    $content .= Form::hidden( "articleId", $this->id );
    $content .= Form::hidden( "albumId", $this->albumId );
    $content .= Form::hidden( "twitterUrl", $this->twitterUrl );
    $content .= Form::hidden( "facebookUrl", $this->facebookUrl );
    $content .= Form::select( "sectionId", $sections, "Section", $this->sectionId );
    $content .= Form::text( "cname", $this->cname, "URL name" );
    $content .= Form::text( "title", $this->title, "Title" );
    $content .= Form::datetime( "ctime", $date, "Date" );
    $content .= Form::textarea( "body", htmlspecialchars($this->body), "Body" );
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
}

?>