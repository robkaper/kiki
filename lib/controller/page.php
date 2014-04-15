<?php

namespace Kiki\Controller;

class Page extends \Kiki\Controller
{
  public function exec()
  {
    $db = \Kiki\Core::getDb();
    $user = \Kiki\Core::getUser();

    $article = new \Kiki\Article( $this->instanceId );
    $this->title = $article->title();

    $template = \Kiki\Template::getInstance();
    $template->append( 'stylesheets', \Kiki\Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

    if ( $article->visible() || $article->userId() == $user->id() )
    {
      $this->title = $article->title();
      $this->status = 200;
      $this->template = 'pages/default';

      $template = new \Kiki\Template( 'content/pages-single' );
      $template->assign( 'page', $article->templateData() );

      $this->content = $template->fetch();
    }
  }
}
