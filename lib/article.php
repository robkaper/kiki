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

namespace Kiki;

class Article extends BaseObject
{
  private $ipAddr = null;
  private $title = null;
  private $cname = null;
  private $summary = null;
  private $body = null;
  private $featured = false;
  private $hashtags = null;
  private $albumId = 0;
  private $visible = true;
  private $sectionId = 0;

  public function reset()
  {
    parent::reset();

    $this->sectionId = null;
    $this->ipAddr = null;
    $this->title = null;
    $this->hashtags = null;
    $this->cname = null;
    $this->summary = null;
    $this->body = null;
    $this->featured = false;
    $this->albumId = 0;
  }

  public function load( $id = 0 )
  {
    if ( $id )
    {
      $this->id = $id;
      $this->object_id = 0;
    }

    $qFields = "id, o.object_id, o.ctime, o.mtime, ip_addr, a.section_id, o.user_id, a.section_id, title, cname, summary, body, featured, a.visible, hashtags, album_id";
    $q = $this->db->buildQuery( "SELECT $qFields FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.id=%d OR o.object_id=%d OR a.cname='%s'", $this->id, $this->object_id, $this->object_id );
    $this->setFromObject( $this->db->getSingleObject($q) );
  }

  public function setFromObject( $o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->sectionId = $o->section_id;
    $this->ipAddr = $o->ip_addr;
    $this->title = $o->title;
    $this->cname = $o->cname;
    $this->summary = $o->summary;
    $this->body = $o->body;
    $this->featured = $o->featured;
    $this->hashtags = $o->hashtags;
    $this->albumId = $o->album_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);

    $qAlbumId = Database::nullable( $this->albumId );

    $q = $this->db->buildQuery(
      "UPDATE articles SET object_id=%d, section_id='%s', ip_addr='%s', title='%s', cname='%s', summary='%s', body='%s', featured=%d, hashtags='%s', album_id=%s where id=%d",
      $this->object_id, $this->sectionId, $this->ipAddr, $this->title, $this->cname, $this->summary, $this->body, $this->featured, $this->hashtags, $qAlbumId, $this->id
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

    $qAlbumId = Database::nullable( $this->albumId );

    $q = $this->db->buildQuery(
      "INSERT INTO articles (object_id, section_id, ip_addr, title, cname, summary, body, featured, hashtags, album_id) VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', %d, '%s', %s)",
      $this->object_id, $this->sectionId, $this->ipAddr, $this->title, $this->cname, $this->summary, $this->body, $this->featured, $this->hashtags, $qAlbumId
    );

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  public function sectionId() { return $this->sectionId; }
  public function setIpAddr( $ipAddr ) { $this->ipAddr = $ipAddr; }
  public function ipAddr() { return $this->ipAddr; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setSummary( $summary ) { $this->summary = $summary; }
  public function summary() { return $this->summary; }
  public function setBody( $body ) { $this->body = $body; }
  public function body() { return $this->body; }
  public function setFeatured( $featured ) { $this->featured = $featured; }
  public function featured() { return $this->featured; }
  public function setHashtags( $hashtags ) { $this->hashtags = $hashtags; }
  public function hashtags() { return $this->hashtags; }
  public function setAlbumId( $albumId ) { $this->albumId = $albumId; }
  public function albumId() { return $this->albumId; }

  public function url( $addSchema = false )
  {
    // FIXME: sectionId is numerical, Router only knows cname...
    $sectionBaseUri = $this->sectionId ? \Kiki\Router::getBaseUri( 'Articles', $this->sectionId ) : null;
    $urlPrefix = ($addSchema ? "https" : null). "//". $_SERVER['SERVER_NAME'];

    // TODO: what if - unlikely, but possible, we have an Article (not Page) with cname index? Really time to go Post/Article/Page
    $url = $urlPrefix. '/'. $sectionBaseUri. '/'. ($this->cname!='index' ? $this->cname : null);

    return $url;
  }

  public static function findbyCname( $cname, $sectionId = null )
  {
    $db = Core::getDb();

    if ( $sectionId )
    {
      $q = "SELECT `id` FROM `articles` WHERE `cname`='%s' AND section_id=%d";
      $q = $db->buildQuery( $q, $cname, $sectionId );
    }
    else
    {
      $q = "SELECT `id` FROM `articles` WHERE `cname`='%s'";
      $q = $db->buildQuery( $q, $cname );
    }

    $id = $db->getSingleValue($q);

    $articleClassName = get_called_class();

    return new $articleClassName($id);
  }

  /**
   * Creates an article edit form.
   *
   * @fixme Should be integrated into a template.
   * @todo Traditional Page class is deprecated so we can now remove the $type argument hack and have a Page/Article class both deriving from Post.
   *
   * @param boolean $hidden Hide the form initially.
   * @param string $type Defaults to 'articles'. When set to 'page', form treats this article as a page.
   *
   * @return string The form HTML.
   *
   */
  public function form( $hidden=false, $type='articles' )
  {
    $user = Core::getUser();

    $date = date( "d-m-Y H:i", $this->ctime ? strtotime($this->ctime) : time() );
    $class = $hidden ? "hidden" : "";

    $sections = array();
    if ( $type=='pages' )
      $sections[0] = 'top-level page';

    $db = Core::getDb();
    $q = $db->buildQuery( "select id,title from sections where type='%s' order by title asc", $type );
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $oSection = $db->fetchObject($rs) )
        $sections[$oSection->id] = $oSection->title;

    return null;
    // TODO: make template based, Form class is deprecated

    $content = Form::open( "articleForm_". $this->id, Config::$kikiPrefix. "/json/article.php", 'POST', $class, "multipart/form-data" );
    $content .= Form::hidden( "articleId", $this->id );
    $content .= Form::hidden( "albumId", $this->albumId );
    $content .= Form::select( "sectionId", $sections, "Section", $this->sectionId );
    
    $content .= Form::text( "title", $this->title, "Title" );

    $content .= Form::text( "cname", $this->cname, "URL name" );

    if ( $type!='pages' )
      $content .= Form::datetime( "ctime", $date, "Date" );

    $content .= Form::textarea( "body", preg_replace( '~\r?\n~', '&#010;', htmlspecialchars($this->body) ), "Body", null, 0, $class );

    if ( $type!='pages' )
      $content .= Form::checkbox( "featured", $this->featured, "Featured" );

    $content .= Form::checkbox( "visible", $this->visible, "Visible" );

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
		// echo $q;
    // print_r( $this->db->getObjectIds( $q ) );
    return $this->db->getObjectIds( $q );
  }

  private function getNext()
  {
		$user = Core::getUser();
    $q = $this->db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND o.ctime>'%s' AND ( (a.visible=1 AND o.ctime<=now()) OR o.user_id=%d) ORDER BY o.ctime ASC LIMIT 1", $this->sectionId, $this->ctime, $user->id() );
    return new Article( $this->db->getSingleValue($q) );
  }
  
  private function getPrev()
  {
		$user = Core::getUser();
    $q = $this->db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND o.ctime<'%s' AND ( (a.visible=1 AND o.ctime<=now()) OR o.user_id=%d) ORDER BY o.ctime DESC LIMIT 1", $this->sectionId, $this->ctime, $user->id() );
    return new Article( $this->db->getSingleValue($q) );
  }

  public function templateData()
  {
    $uAuthor = new User( $this->user_id ); // ObjectCache::getByType( 'Futunk\User', $this->user_id );

    $prevArticle = $this->getPrev();
    $nextArticle = $this->getNext();

    $data = array(
      'id' => $this->id,
      'url' => $this->url(),
      'ctime' => strtotime($this->ctime),
      'relTime' => Misc::relativeTime($this->ctime),
      'title' => $this->title,
      'summary' => $this->summary,
      'body' => $this->body,
      'author' => $uAuthor->name(),
      'images' => array(),
      'likes' => $this->likes(),
      'comments' => Comments::count( $this->object_id ),
      'html' => array(
        'comments' => Comments::show( $this->object_id ),
        'editform' => $this->form( true, 'articles' )
      )
    );
    
    if ( $nextArticle = $this->getNext() )
    {
      $data['next'] =array(
        'id'=> $nextArticle->id(),
        'url' => $nextArticle->url(),
        'title' => $nextArticle->title()
      );
    }

    if ( $prevArticle = $this->getPrev() )
    {
      $data['prev'] =array(
        'id'=> $prevArticle->id(),
        'url' => $prevArticle->url(),
        'title' => $prevArticle->title()
      );
    }

    $images = $this->images();
    foreach( $images as $image )
      $data['images'][] = Storage::url($image);

    return $data;
  }

}
