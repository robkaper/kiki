<?php

class Controller_Articles extends Controller
{
  public function exec()
  {
    $db = Kiki::getDb();
    $user = Kiki::getUser();

    $template = Template::getInstance();
    $template->append( 'stylesheets', Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    $q = $db->buildQuery( "SELECT id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id WHERE o.section_id=%d AND ((o.visible=1 AND o.ctime<=now()) OR o.user_id=%d) ORDER BY o.ctime DESC LIMIT 10", $this->instanceId, $user->id() );
    $articleIds = $db->getArray($q);
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
			$matches = array();
			if ( preg_match( '/^socialupdate-([\d]+)$/', $this->objectId, $matches ) && isset($matches[1]) )
			{
				$updateId = $matches[1];
				$update = new SocialUpdate( $updateId );

				$this->status = 200;
				$this->title  = Misc::textSummary( $update->body(), 50 );
				$this->template = 'pages/default';

        $template = new Template( 'content/socialupdates-single' );
        $template->assign( 'update', $update->templateData() );

        $this->content = $template->fetch();

				return;
			}

      $article = new Article( 0, $this->objectId);
      if ( $article->id() && ( $article->visible() || $article->userId() == $user->id() ) )
      {
        $this->status = 200;
        $this->title = $article->title();
        $this->template = 'pages/default';

        $template = new Template( 'content/articles-single' );
				$GLOBALS['articleAlbumId'] = $article->albumId();
        $template->assign( 'article', $article->templateData() );

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
      $section = new Section( $this->instanceId );

			$itemsPerPage = 25;
			if ( !isset($currentPage) )
				$currentPage = 1;

      $this->status = 200;
      $this->title = $section->title();
      $this->template = 'pages/default';

      $this->content = MultiBanner::articles( $section->id() );

      $article = new Article();
			$update = new SocialUpdate();

			$q = $db->buildQuery( "SELECT count(*) FROM objects WHERE type IN ('socialupdate', 'article') AND section_id=%d AND ((visible=1 AND ctime<=now()) OR user_id=%d)", $this->instanceId, $user->id() );
			$totalPosts = $db->getSingleValue($q);

			$paging = new Paging();
			$paging->setCurrentPage( $currentPage );
			$paging->setItemsPerPage( $itemsPerPage );
			$paging->setTotalItems( $totalPosts );

      $q = $db->buildQuery( "SELECT object_id, ctime, type FROM objects WHERE type IN ('socialupdate', 'article') AND section_id=%d AND ( (visible=1 AND ctime<=now()) OR user_id=%d) ORDER BY ctime DESC LIMIT %d,%d", $this->instanceId, $user->id(), $paging->firstItem()-1, $itemsPerPage );
			$rs = $db->query($q);
			while( $o = $db->fetchObject($rs) )
			{
				switch( $o->type )
				{
					case 'Article':
						$article->reset();
						$article->setObjectId( $o->object_id );
						$article->load();

						$template = new Template( 'content/articles-summary' );
						$template->assign( 'article', $article->templateData() );

						$this->content .= $template->fetch();
						break;

					case 'SocialUpdate':
						$update->reset();
						$update->setObjectId( $o->object_id );
						$update->load();

	          $template = new Template( 'content/socialupdates-summary' );
	          $template->assign( 'update', $update->templateData() );

						$this->content .= $template->fetch();
						break;

					default:;
				}
      }

			$this->content .= $paging->html();
    }

  }

}
  
?>