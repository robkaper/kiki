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
    {
      // TODO: refactor into detailAction();
      $this->objectId = $this->action ?? null;
      return $this->indexAction();
    }

    return $this->indexAction();
  }

  public function indexAction()
  {
    if ( !$this->context )
      return false;

    $this->initTemplateData();

    $this->objectId = $this->action ?? null;

    $db = Core::getDb();
    $user = Core::getUser();

    $template = Template::getInstance();

    $this->instanceId = Section::getIdFromBaseUri($this->context);

    ob_start();

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

    if ( isset($this->objectId) && $this->objectId )
    {
      // FIXME: re-add visibility check, along with properly adding p(ublish)time
      $article = Article::findByCname( $this->objectId, $this->instanceId );
      if ( $article->id() && $article->sectionId() == $this->instanceId )
      {
        $this->status = 200;
        $this->title = $article->title();
        $this->template = 'pages/articles/detail';

        $template = new Template( 'content/articles-single' );
        $template->assign( 'article', $article->templateData() );

        $album = Album::findByLinkedObjectId( $article->objectId() );
        $picture = new Picture( $album->getHighlightId() );
        $storageItem = new StorageItem( $picture->storageId() );
        $template->assign( 'image', $storageItem->url() );

        $this->content = $template->fetch();
      }
      else
      {
        // $this->template = 'pages/default';

        // $template = new Template( 'content/articles-404' );
        // $this->content = $template->fetch();

        return false;
      }
    }
    else
    {
      $section = new \Kiki\Section( $this->instanceId );

      $itemsPerPage = 25;
      if ( !isset($currentPage) )
        $currentPage = 1;

      $this->status = 200;
      $this->title = $section->title();
      $this->template = 'pages/articles/index';

      $this->content = null; // MultiBanner::articles( $section->id() );

      $article = new Article();

      $q = $db->buildQuery( "SELECT count(*)
        FROM articles a, objects o
        WHERE o.object_id=a.object_id
        AND type IN ('%s', '%s')
          AND a.section_id=%d AND ((a.visible=1 AND ctime<=now()) OR user_id=%d)",
        'Article', 'Kiki\Article',
        $this->instanceId,
        $user->id()
      );
      $totalPosts = $db->getSingleValue($q);

      $paging = new \Kiki\Paging();
      $paging->setCurrentPage( $currentPage );
      $paging->setItemsPerPage( $itemsPerPage );
      $paging->setTotalItems( $totalPosts );

      $q = $db->buildQuery( "SELECT o.object_id, ctime, type
        FROM articles a, objects o
        WHERE o.object_id=a.object_id
        AND type IN ('%s', '%s')
        AND section_id=%d AND ( (a.visible=1 AND ctime<=now()) OR user_id=%d)
        ORDER BY ctime DESC
        LIMIT %d,%d",
        'Article',
        'Kiki\Article',
        $this->instanceId,
        $user->id(),
        $paging->firstItem()-1,
        $itemsPerPage
      );
      $articles = $db->getObjects($q);

      foreach( $articles as $dbArticle )
      {
        switch( $dbArticle->type )
        {
          case 'Article':
          case 'Kiki\Article':
            $article->reset();
            $article->setObjectId( $dbArticle->object_id );
            $article->load();
//            $article->setSectionId( $this->context );

            $template = new Template( 'content/articles-summary', true );
            $template->assign( 'article', $article->templateData() );

            $album = Album::findByLinkedObjectId( $article->objectId() );
            $picture = new Picture( $album->getHighlightId() );
            $storageItem = new StorageItem( $picture->storageId() );
            $template->assign( 'image', $storageItem->url() );

            $this->content .= $template->fetch();
            break;

          default:;
        }
      }

      $this->content .= $paging->html();
    }

    $this->content .= ob_get_contents();
    ob_end_clean();

    return true;
  }

}
