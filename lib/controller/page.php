<?php

namespace Kiki\Controller;

use Kiki\Core;
use Kiki\Template;

class Page extends \Kiki\Controller
{
  public function exec()
  {
    $db = Core::getDb();
    $user = Core::getUser();

    $template = Template::getInstance();
    $template->load( 'kiki/index' );

    $this->title = 'Kiki';
    $this->template = 'kiki/index';
    $this->status = 200;

    $this->content = '<p>Welcome to Kiki.</p>';

    return true;

    if ( $article->visible() || $article->userId() == $user->id() )
    {
      $this->title = $article->title();
      $this->status = 200;
      $this->template = 'pages/default';

      $template = new Template( 'content/pages-single' );
      $template->assign( 'page', $article->templateData() );

      $this->content = $template->fetch();
    }
  }
}
