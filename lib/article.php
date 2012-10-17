<?php

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
    $this->albumId = 0;
  }

  public function load( $id = 0 )
  {
    if ( $id )
    {
      $this->id = $id;
      $this->objectId = 0;
    }

    // FIXME: provide an upgrade path removing ctime/atime from table, use objects table only, same for saving
    $qFields = "id, o.object_id, a.ctime, a.mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, hashtags, album_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.id=%d OR a.object_id=%d OR a.cname='%s'", $this->id, $this->objectId, $this->objectId );
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
    $this->hashtags = $o->hashtags;
    $this->albumId = $o->album_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);

    $q = $this->db->buildQuery(
      "UPDATE articles SET object_id=%d, ctime='%s', mtime=now(), ip_addr='%s', section_id=%d, user_id=%d, title='%s', cname='%s', body='%s', header_image=%d, featured=%d, visible=%d, hashtags='%s', album_id=%d where id=%d",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->hashtags, $this->albumId, $this->id
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
      "INSERT INTO articles (object_id, ctime, mtime, ip_addr, section_id, user_id, title, cname, body, header_image, featured, visible, hashtags, album_id) VALUES (%d, '%s', now(), '%s', %d, %d, '%s', '%s', '%s', %d, %d, %d, '%s', %d)",
      $this->objectId, $this->ctime, $this->ipAddr, $this->sectionId, $this->userId, $this->title, $this->cname, $this->body, $this->headerImage, $this->featured, $this->visible, $this->hashtags, $this->albumId
    );

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
  public function setHashtags( $hashtags ) { $this->hashtags = $hashtags; }
  public function hashtags() { return $this->hashtags; }
  public function setAlbumId( $albumId ) { $this->albumId = $albumId; }
  public function albumId() { return $this->albumId; }

  public function url()
  {
    $sectionBaseUri = Router::getBaseUri( 'articles', $this->sectionId );
    if ( !$sectionBaseUri )
      $sectionBaseUri = Router::getBaseUri( 'pages', $this->sectionId );
    if ( !$sectionBaseUri )
      $sectionBaseUri = "/";

    $urlPrefix = "http://". $_SERVER['SERVER_NAME'];

    // TODO: what if - unlikely, but possible, we have an Article (not Page) with cname index? Really time to go Post/Article/Page
    $url = $urlPrefix. $sectionBaseUri. ($this->cname!='index' ? $this->cname : null);

    return $url;
  }

  /**
   * Creates an article edit form.
   *
   * @fixme Should be integrated into a template.
   * @todo Traditional Page class is deprecated so we can now remove the $type argument hack and have a Page/Article class both deriving from Post.
   *
   * @param User $user User object, used to show the proper connection links for publications.
   * @param boolean $hidden Hide the form initially.
   * @param string $type Defaults to 'articles'. When set to 'page', form treats this article as a page.
   *
   * @return string The form HTML.
   *
   */
  public function form( &$user, $hidden=false, $type='articles' )
  {
    $date = date( "d-m-Y H:i", $this->ctime ? strtotime($this->ctime) : time() );
    $class = $hidden ? "hidden" : "";

    $sections = array();
    if ( $type=='pages' )
      $sections[0] = 'top-level page';

    $db = $GLOBALS['db'];
    $q = $db->buildQuery( "select id,title from sections where type='%s' order by title asc", $type );
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $oSection = $db->fetchObject($rs) )
        $sections[$oSection->id] = $oSection->title;

    $content = Form::open( "articleForm_". $this->id, Config::$kikiPrefix. "/json/article.php", 'POST', $class, "multipart/form-data" );
    $content .= Form::hidden( "articleId", $this->id );
    $content .= Form::hidden( "albumId", $this->albumId );
    $content .= Form::select( "sectionId", $sections, "Section", $this->sectionId );
    
    $this->loadPublications();
    
    $content .= Form::text( "title", $this->title, "Title" );

    if ( !count($this->publications) )
      $content .= Form::text( "cname", $this->cname, "URL name" );

    if ( $type!='pages' )
      $content .= Form::datetime( "ctime", $date, "Date" );

    $class = Config::$clEditor ? "cleditor" : null;
    $content .= Form::textarea( "body", preg_replace( '~\r?\n~', '&#010;', htmlspecialchars($this->body) ), "Body", null, 0, $class );
    $content .= Form::albumImage( "headerImage", "Header image", $this->albumId, $this->headerImage );

    if ( $type!='pages' )
      $content .= Form::checkbox( "featured", $this->featured, "Featured" );

    $content .= Form::checkbox( "visible", $this->visible, "Visible" );

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

  public function templateData()
  {
    $uAuthor = ObjectCache::getByType( 'User', $this->userId );
    $publications = $this->publications();

    $data = array(
      'id' => $this->id,
      'url' => $this->url(),
      'ctime' => strtotime($this->ctime),
      'relTime' => Misc::relativeTime($this->ctime),
      'title' => $this->title,
      'body' => $this->body,
      'author' => $uAuthor->name(),
      'headerImage' => Storage::url($this->headerImage),
      'publications' => array(),
      'likes' => $this->likes(),
      'html' => array(
        'comments' => Comments::show( $this->db, $GLOBALS['user'], $this->objectId ),
        'editform' => $this->form( $GLOBALS['user'], true, 'articles' )
      )
    );

    foreach( $publications as $publication )
      $data['publications'][] = $publication->templateData();

    return $data;
  }

}

?>