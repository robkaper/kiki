<?php

/**
 * Router script for Kiki. Should be called for all URI's where there is no
 * direct local file match.  Relays content handling to various controllers
 * based on URI configurations.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo extend to minimise or totally make mod_rewrite optional
 * @warning album URLs currently broken
 */

  // Optimisation: pre-recognise static files (skips i18n, database and user handling in init.php)
  $staticFile = preg_match( '#^/kiki/(.*)\.(css|gif|jpg|js|png)#', $_SERVER['REQUEST_URI'] );
  
  require_once "../lib/init.php";

  Log::debug( "START router.php: $reqUri", $staticFile );

  // Redirect requests with parameters we don't want visible for the user or
  // Analytics.
  if ( isset($_GET['fb_xd_fragment']) || (isset($_GET['state']) && isset($_GET['code'])) )
  {
    Router::redirect( $_SERVER['SCRIPT_URL'], 301 ) && exit();
  }

  $controller = null;

  // TinyURLs
  if ( preg_match('#^/[0-9a-zA-Z]{3}$#', $reqUri) )
  {
    $controller = Controller::factory('TinyUrl');
    $controller->setObjectId( substr($reqUri, 1) );
  }

  // Kiki base files
  else if ( preg_match('#^/kiki/(.*)#', $reqUri, $matches) )
  {
    if ( preg_match('#^(album|event)/(.*)#', $matches[1], $moduleMatches) )
    {
      $controller = Controller::factory( $moduleMatches[1] );
      $controller->setObjectId( $moduleMatches[2] );
    }
    else
    {
      $controller = Controller::factory('Kiki');
      $controller->setObjectId( $matches[1] );
    }
  }

  // Automatic thumbnails for storage files
  else if ( preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)\.((c?))?#', $reqUri, $matches) )
  {
    $controller = Controller::factory('Thumbnails');
    $controller->setObjectId($matches);
  }

  // Check if URI contains a base handled by a dynamic controller
  else if ( !($controller = Router::findPage($reqUri)) && !($controller = Router::findSection($reqUri)) )
  {
	  // Nothing? Default controller (404 page)
    $controller = new Controller();
	}

  $controller->exec();

  Http::sendHeaders( $controller->status() );
  
  if ( $controller->status() == 301 )
    Router::redirect($controller->content(), $controller->status()) && exit();

  $content = $controller->content();

  $title = $controller->title();
  if ( $title )
    $title .= " - ";
  $title .= Config::$siteName;

  // if ( $var = $template->getVar('subTitle') )
  //  $title .= " - ". $var;

  $template = Template::getInstance();
  $template->assign( 'footerText', Boilerplate::copyright() );
    
  $template->load( $controller->template() );

  $template->assign( 'title', $controller->title() );
  // $template->assign( 'subTitle', $controller->subTitle() );
  // $template->assign( 'description', $controller->description() );
        
  $template->assign( 'content', $controller->content() );

  echo $template->content();

  Log::debug( "END router.php: $reqUri" );
?>
