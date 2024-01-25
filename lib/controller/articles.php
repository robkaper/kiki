<?php

namespace Kiki\Controller;

use Kiki\Core;
use Kiki\Template;
use Kiki\Config;

use Kiki\Article;
use Kiki\Section;
use Kiki\Album;
use Kiki\Picture;
use Kiki\StorageItem;

// TODO: Articles and Pages are very alike and practically only vary in how templates display them, consider refactoring into inherited Post/PostCollection controllers
class Articles extends \Kiki\Controller
{
  public function actionHandler()
  {
    if ( $this->action )
      return $this->detailAction();

    return $this->indexAction();
  }

  public function indexAction()
  {
    if ( !$this->context )
      return false;

    $this->initTemplateData();

    $db = Core::getDb();
    $user = Core::getUser();

    $this->instanceId = Section::getIdFromBaseUri($this->context);

    $template = Template::getInstance();

/*
    $q = $db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND ((a.visible=1 AND o.ctime<=now()) OR o.user_id=%d) ORDER BY o.ctime DESC LIMIT 10", $this->instanceId, $user->id() );

    $articleIds = $db->getObjectIds($q);
    $articles = array();
    foreach ( $articleIds as $articleId )
    {
      $article = new Article( $articleId );
      $articles[] = array( 'url' => $article->url(), 'title' => $article->title() );
    }
    $template->assign( 'latestArticles', $articles );

    if ( preg_match( '/^page-([\d]+)$/', $this->objectId, $matches ) && isset($matches[1]) )
    {
      $this->objectId = null;
      $currentPage = $matches[1];
    }
*/

    $section = new \Kiki\Section( $this->instanceId );

    $itemsPerPage = 25;
    if ( !isset($currentPage) )
      $currentPage = 1;

    $this->status = 200;
    $this->title = $section->title();
    $this->template = 'pages/articles/index';

    $this->content = null; // MultiBanner::articles( $section->id() );

    $q = $db->buildQuery(
      "SELECT COUNT(*)
      FROM articles a
      WHERE a.section_id=%d AND ptime<=NOW()",
      $this->instanceId,
    );
    $totalPosts = $db->getSingleValue($q);

    $paging = new \Kiki\Paging();
    $paging->setCurrentPage( $currentPage );
    $paging->setItemsPerPage( $itemsPerPage );
    $paging->setTotalItems( $totalPosts );

    $q = $db->buildQuery(
      "SELECT id
      FROM articles a
      WHERE section_id=%d AND a.ptime<=NOW()
      ORDER BY ptime DESC
      LIMIT %d,%d",
      $this->instanceId,
      $paging->firstItem()-1,
      $itemsPerPage
    );
    $articles = $db->getObjects($q);

    $article = new Article();

    foreach( $articles as $dbArticle )
    {
      $article->reset();
      $article->load( $dbArticle->id );

      $template = new Template( 'content/articles-summary' );
      $template->assign( 'article', $article->templateData() );

      $album = Album::findByLinkedObjectId( $article->objectId() );
      $picture = new Picture( $album->getHighlightId() );
      $storageItem = new StorageItem( $picture->storageId() );
      $template->assign( 'image', $storageItem->url() );

      $this->content .= $template->fetch();
    }

    $this->content .= $paging->html();

    return true;
  }

  public function detailAction()
  {
    $this->objectId = $this->action ?? null;

    if ( !$this->context )
      return false;

    $this->initTemplateData();

    $db = Core::getDb();
    $user = Core::getUser();

    $template = Template::getInstance();

    $this->instanceId = Section::getIdFromBaseUri($this->context);

/*
    $q = $db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.section_id=%d AND ((a.visible=1 AND o.ctime<=now()) OR o.user_id=%d) ORDER BY o.ctime DESC LIMIT 10", $this->instanceId, $user->id() );

    $articleIds = $db->getObjectIds($q);
    $articles = array();
    foreach ( $articleIds as $articleId )
    {
      $article = new Article( $articleId );
      $articles[] = array( 'url' => $article->url(), 'title' => $article->title() );
    }
    $template->assign( 'latestArticles', $articles );
*/

    // FIXME: re-add visibility check, along with properly adding p(ublish)time
    $article = Article::findByCname( $this->objectId, $this->instanceId );
    if ( !$article->id() )
      return false;

    $this->status = 200;
    $this->title = $article->title();
    $this->template = 'pages/articles/detail';

    $template = new Template( 'content/articles-single', $this->data() );
    $template->assign( 'article', $article->templateData() );

    $album = Album::findByLinkedObjectId( $article->objectId() );
    $picture = new Picture( $album->getHighlightId() );
    $storageItem = new StorageItem( $picture->storageId() );
    $template->assign( 'image', $storageItem->url() );

    $likes = $article->likes( $user->id() );
    $comments = $article->comments( $user->id() );

    $template->assign( 'object_id', $article->objectId() );
    $template->assign( 'likes', $likes );
    $template->assign( 'comments', $comments );

    $this->content = $template->fetch();

    return true;
  }
}
