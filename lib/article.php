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
 * @copyright 2011-2024 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Article extends BaseObject
{
  private $ptime = null;

  private $sectionId = 0;
  private $cname = null;

  private $title = null;
  private $summary = null;
  private $body = null;

  public function reset()
  {
    parent::reset();

    $this->sectionId = null;
    $this->cname = null;

    $this->title = null;
    $this->summary = null;
    $this->body = null;
  }

  public function load( $id = 0 )
  {
    if ( $id )
    {
      $this->id = $id;
      $this->object_id = 0;
    }

    $qFields = "id, o.object_id, o.object_name, o.user_id, o.ctime, o.mtime, a.ptime, a.section_id, a.cname, a.title, a.summary, a.body";
    $q = $this->db->buildQuery( "SELECT %s FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.id=%d OR a.object_id=%d", $qFields, $this->id, $this->object_id );
    $this->setFromObject( $this->db->getSingleObject($q) );
  }

  public function setFromObject( $o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->ptime = $o->ptime;

    $this->sectionId = $o->section_id;
    $this->cname = $o->cname;

    $this->title = $o->title;

    $this->summary = $o->summary;
    $this->body = $o->body;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);

    if ( !$this->ptime )
      $this->ptime = time();
    if ( is_int($this->ptime) )
      $this->time = date( 'Y-m-d H:i:s', $this->ptime );

    $q = $this->db->buildQuery(
      "UPDATE articles SET object_id=%d, ptime='%s', section_id=%d, cname='%s', title='%s', summary='%s', body='%s' WHERE id=%d",
      $this->object_id, $this->ptime, $this->sectionId, $this->cname, $this->title, $this->summary, $this->body, $this->id
    );

    $this->db->query($q);
  }

  public function dbInsert()
  {
    if ( !$this->cname )
      $this->cname = Misc::uriSafe($this->title);

    if ( !$this->ptime )
      $this->ptime = time();
    if ( is_int($this->ptime) )
      $this->time = date( 'Y-m-d H:i:s', $this->ptime );

    $q = $this->db->buildQuery(
      "INSERT INTO articles (object_id, ptime, section_id, cname, title, summary, body) VALUES (%d, '%s', %d, '%s', '%s', '%s', '%s')",
      $this->object_id, $this->ptime, $this->sectionId, $this->cname, $this->title, $this->summary, $this->body
    );

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function setPublishTime( $ptime ) { $this->ptime = $ptime; }
  public function publishTime() { return $this->ptime; }
  public function setSectionId( $sectionId ) { $this->sectionId = $sectionId; }
  public function sectionId() { return $this->sectionId; }
  public function setCname( $cname ) { $this->cname = $cname; }
  public function cname() { return $this->cname; }
  public function setTitle( $title ) { $this->title = $title; $this->objectName = $title; }
  public function title() { return $this->title; }
  public function setSummary( $summary ) { $this->summary = $summary; }
  public function summary() { return $this->summary; }
  public function setBody( $body ) { $this->body = $body; }
  public function body() { return $this->body; }

  public function url( $addHost = false, $addSchema = false )
  {
    $sectionBaseUri = $this->sectionId ? \Kiki\Router::getBaseUri( 'Articles', $this->sectionId ) : null;

    $urlPrefix = ($addSchema ? "https:" : null). ($addHost ? "//". $_SERVER['SERVER_NAME'] : null);

    $url = $urlPrefix. '/'. $sectionBaseUri. '/'. $this->cname;

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

  // TODO: removed Form:: based edit form, create template for it
  // TODO: rewrite getNext() getPrev()
  private function getNext()
  {
    $user = Core::getUser();

    $q = $this->db->buildQuery(
      "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND a.ptime>'%s' AND a.ptime<=NOW() ORDER BY a.ptime ASC LIMIT 1",
      $this->sectionId, $this->ptime
    );

    $articleClassName = get_called_class();
    return new $articleClassName( $this->db->getSingleValue($q) );
  }

  private function getPrev()
  {
    $user = Core::getUser();

    $q = $this->db->buildQuery(
      "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND a.ptime<'%s' AND a.ptime<=NOW() ORDER BY a.ptime ASC LIMIT 1",
      $this->sectionId, $this->ptime
    );

    $articleClassName = get_called_class();
    return new $articleClassName( $this->db->getSingleValue($q) );
  }

  public function templateData()
  {
    $uAuthor = new User( $this->user_id ); // ObjectCache::getByType( 'User', $this->user_id );

    $data = [
      'id' => $this->id,
      'url' => $this->url(),
      'ctime' => strtotime($this->ctime),
      'mtime' => strtotime($this->mtime),
      'ptime' => strtotime($this->ptime),
      'useRelTime' => ( time() - strtotime($this->ptime) < 10 * 86400 ),
      'relTime' => Misc::relativeTime($this->ptime),
      'title' => $this->title,
      'summary' => $this->summary,
      'body' => $this->body,
      'author' => $uAuthor->name(),
      'likes' => $this->likes(),
    ];

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

    return $data;
  }
}
