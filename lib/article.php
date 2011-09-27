<?

class Article extends Object
{
  private $ipAddr = null;
  private $sectionId = null;
  private $userId = null;
  private $title = null;
  private $cname = null;
  private $body = null;
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
    $this->visible = null;
    $this->facebookUrl = null;
    $this->twitterUrl = null;
  }

  public function load()
  {
    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    $q = $this->db->buildQuery( "SELECT id, o.object_id, a.ctime, a.mtime, ip_addr, section_id, user_id, title, cname, body, visible, facebook_url, twitter_url FROM articles a LEFT JOIN objects o on o.object_id=a.object_id where id=%d", $this->id );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    $this->ipAddr = $o->ip_addr;
    $this->sectionId = $o->section_id;
    $this->userId = $o->user_id;
    $this->title = $o->title;
    $this->cname = $o->cname;
    $this->body = $o->body;
    $this->visible = $o->visible;
    $this->facebookUrl = $o->facebook_url;
    $this->twitterUrl = $o->twitter_url;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE articles SET object_id=%d, ctime='%s', mtime=now(), ip_addr='%s', section_id=%d, user_id=%d, title='%s', cname='%s', body='%s', visible=%d, facebook_url='%s', twitter_url='%s' where id=%d",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->id
    );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);
    if ( !$this->ctime )
      $this->ctime = date("Y-m-d H:i:s");

    $q = $this->db->buildQuery(
      "INSERT INTO articles (object_id, ctime, mtime, ip_addr, section_id, user_id, title, cname, body, visible, facebook_url, twitter_url) VALUES (%d, '%s', now(), '%s', %d, %d, '%s', '%s', '%s', %d, '%s', '%s')",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->visible, $this->facebookUrl, $this->twitterUrl
    );
    
    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  public function sectionId() { return $this->sectionId; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setBody( $body ) { $this->body = $body; }
  public function body() { return $this->body; }

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