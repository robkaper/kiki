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

  public function reset()
  {
    parent::reset();

    $this->ipAddr = null;
    $this->sectionId = null;
    $this->userId = null;
    $this->title = null;
    $this->cname = null;
    $this->body = null;
    $this->headerImage = null;
    $this->featured = false;
    $this->visible = false;
    $this->facebookUrl = null;
    $this->twitterUrl = null;
  }

  public function load()
  {
    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    $qFields = "id, o.object_id, a.ctime, a.mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, facebook_url, twitter_url";
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
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE articles SET object_id=%d, ctime='%s', mtime=now(), ip_addr='%s', section_id=%d, user_id=%d, title='%s', cname='%s', body='%s', header_image=%d, featured=%d, visible=%d, facebook_url='%s', twitter_url='%s' where id=%d",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->id
    );
    Log::debug($q);

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);
    if ( !$this->ctime )
      $this->ctime = date("Y-m-d H:i:s");

    $q = $this->db->buildQuery(
      "INSERT INTO articles (object_id, ctime, mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, facebook_url, twitter_url) VALUES (%d, '%s', now(), '%s', %d, %d, '%s', '%s', '%s', %d, %d, '%s', '%s')",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->facebookUrl, $this->twitterUrl
    );
    Log::debug($q);

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

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

  public function url()
  {
    $sectionBaseUri = Router::getBaseUri( 'articles', $this->sectionId );
    if ( !$sectionBaseUri )
      $sectionBaseUri = "/";
    $urlPrefix = "http://". $_SERVER['SERVER_NAME'];
    $url = $urlPrefix. $sectionBaseUri. $this->cname;
    return $url;
  }
}

?>