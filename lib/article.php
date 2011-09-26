<?

class Article
{
  private $db = null;

  private $id = 0;
  private $ctime = null;
  private $mtime = null;
  private $ipAddr = null;
  private $sectionId = null;
  private $userId = null;
  private $title = null;
  private $cname = null;
  private $body = null;
  private $visible = false;
  private $facebookUrl = null;
  private $twitterUrl = null;

  public function __construct( $id=0 )
  {
    $this->db = $GLOBALS['db'];

    if ( $this->id = $id )
      $this->load();
  }

  public function reset()
  {
  }

  public function load()
  {
    $q = $this->db->buildQuery( "SELECT id, ctime, mtime, ip_addr, section_id, user_id, title, cname, body, visible, facebook_url, twitter_url FROM articles where id=%d", $this->id );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    $this->id = $o->id;
    $this->ctime = $o->ctime;
    $this->mtime = $o->mtime;
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

  public function save()
  {
    $this->id ? $this->dbUpdate() : $this->dbInsert();
  }
  
  public function dbUpdate()
  {
    $q = $this->db->buildQuery(
      "UPDATE articles SET ctime='%s', mtime=now(), ip_addr='%s', section_id=%d, user_id=%d, title='%s', cname='%s', body='%s', visible=%d, facebook_url='%s', twitter_url='%s' where id=%d",
      $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->visible, $this->facebookUrl, $this->twitterUrl, $this->id
    );

    $this->db->query();
  }
  
  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO articles (ctime, mtime, ip_addr, section_id, user_id, title, cname, body, visible, facebook_url, twitter_url) VALUES ('%s', now(), '%s', %d, %d, '%s', '%s', '%s', %d, '%s', '%s')",
      $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->visible, $this->facebookUrl, $this->twitterUrl
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