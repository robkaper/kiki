<?php

class Controller_Page extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $article = new Article( $this->instanceId );
    $this->title = $article->title();

    $template = Template::getInstance();
    $template->append( 'stylesheets', Config::$kikiPrefix. "/scripts/prettify/prettify.css" );

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
  
?>