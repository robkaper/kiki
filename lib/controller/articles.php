<?php

class Controller_Articles extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $template = Template::getInstance();
    $template->append( 'stylesheets', Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    $q = $db->buildQuery( "select id from articles where section_id=%d and visible=1 order by ctime desc limit 10", $this->instanceId );
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
			unset($this->objectId);
			$currentPage = $matches[1];
		}

    if ( $this->objectId )
    {
      $article = new Article( 0, $this->objectId);
      if ( $article->id() && ( $article->visible() || $article->userId() == $user->id() ) )
      {
        $this->status = 200;
        $this->title = $article->title();
        $this->template = 'pages/default';

        $template = new Template( 'content/articles-single' );
        $template->assign( 'article', $article->templateData() );

        $this->content = $template->fetch();
      }
      else
      {
        Log::debug("article404");
        // TODO: set custom 404 template
        return false;
      }
    }
    else
    {
      $section = new Section( $this->instanceId );

      $this->status = 200;
      $this->title = $section->title();
      $this->template = 'pages/default';

      $this->content = MultiBanner::articles( $section->id() );

			$itemsPerPage = 10;

      $article = new Article();

			if ( !isset($currentPage) )
				$currentPage = 1;

      $q = $db->buildQuery( "SELECT count(*) FROM articles WHERE section_id=%d AND ( (visible=1 AND ctime<=now()) OR user_id=%d)", $this->instanceId, $user->id() );
			$totalArticles = $db->getSingleValue($q);

			$paging = new Paging();
			$paging->setCurrentPage( $currentPage );
			$paging->setItemsPerPage( $itemsPerPage );
			$paging->setTotalItems( $totalArticles );

      $q = $db->buildQuery( "SELECT id FROM articles WHERE section_id=%d AND ( (visible=1 AND ctime<=now()) OR user_id=%d) ORDER BY ctime DESC LIMIT %d,%d", $this->instanceId, $user->id(), $paging->firstItem()-1, $itemsPerPage );
      $articleIds = $db->getArray($q);

      foreach( $articleIds as $articleId )
      {
        $article->load($articleId);

        $template = new Template( 'content/articles-summary' );
        $template->assign( 'article', $article->templateData() );

        $this->content .= $template->fetch();
      }

			$this->content .= $paging->html();
    }

  }

}
  
?>