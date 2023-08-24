<?php

/**
 * Class providing controller factory and base class.
 *
 * @todo Split into a proper base class abstract and factory.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Controller
{
  protected $instanceId = 0;
  protected $objectId = 0;

  protected $action = null;
  protected $context = null;

  protected $contentType = 'html';

  protected $status = 404;
  protected $altContentType = null;
  protected $title = '404 Not found';
  protected $template = 'pages/404';
  protected $data = null;
  protected $content = null;

  // FIXME: refactor to template data, or Core class
  protected $notices = null;
  protected $warnings = null;
  protected $errors = null;

  protected $subController = null;

  protected $extraMeta = null;
  protected $extraScripts = null;
  protected $extraStyles = null;

  public function __construct()
  {
    $this->extraMeta = array();
    $this->extraScripts = array();
    $this->extraStyles = array();

    $this->data = array();

    $this->notices = array();
    $this->warnings = array();
    $this->errors = array();
  }

  public static function factory($type)
  {
    $className = ClassHelper::typeToClass($type);
    // echo "<br>a$className";

    if ( !class_exists($className) )
      $className = \Kiki\Config::$namespace. "\\". ClassHelper::typeToClass($type);
    // echo "<br>b$className";

    if ( !class_exists($className, false) )
      $className = __NAMESPACE__. "\\". ClassHelper::typeToClass($type);
    // echo "<br>c$className";

    if ( !class_exists($className) )
    {
      $classFile = ClassHelper::classToFile($className);
      // SNH because class loader would've already called it?
      Log::error( "class $className not defined in $classFile" );
      return new Controller();
    }

    return new $className;
  }

  public function type()
  {
    return ClassHelper::classToType( get_called_class() );
  }

  public function setAction( $action )
  {
    $this->action = $action;
  }
  public function action() { return $this->action; }

  public function setContext( $context )
  {
    $this->context = $context;
  }
  public function context() { return $this->context; }

  public function setInstanceId( $instanceId )
  {
    $this->instanceId = $instanceId;
  }

  public function instanceId() { return $this->instanceId; }

  public function setObjectId( $objectId )
  {
    $this->objectId = $objectId;
  }

  public function objectId() { return $this->objectId; }

  protected function actionHandler()
  {
    $remainder = null;

    $urlParts = parse_url($this->action ?? $this->objectId);
    if ( isset($urlParts['path']) && !empty($urlParts['path']) )
    {
      $pathParts = explode( '/', $urlParts['path'] );
      $action = array_shift($pathParts);
      $actionMethod = str_replace( '-', '_', $action ). 'Action';

      $remainder = implode( '/', $pathParts );
    }
    else
    {
      $action = 'index';
      $actionMethod = 'indexAction';
      $remainder = $this->objectId;
    }

    if ( $this->subController = $this->getActionController($action) )
    {
      $this->action = $remainder;

      Log::debug( "found subController: ". get_class($this->subController). "->actionHandler, action: $action, remainder: $remainder" );
      return $this->subController->actionHandler();
    }

    if ( !method_exists($this, $actionMethod) )
      return false;

    Log::debug( "found actionMethod: ". get_class($this). "->$actionMethod, remainder: $remainder" );

    $ret = $this->$actionMethod($remainder);
    if  ( $ret )
    {
      // Fallbacks for when template doesn't do it
      if ( $this->status == 404 )
        $this->status = 200;

      if ( !$this->title )
        $this->title = $actionMethod;

      if ( $this->template == 'pages/404' )
        $this->template = 'pages/default';
    }

    return $ret;
  }

  public function getActionController($action)
  {
    $actionController = get_class($this). "\\". ucfirst($action);
    Log::debug( "trying $actionController" );
    if ( class_exists($actionController) )
      return new $actionController();

    return null;
  }

  // Exists because actionHandler is protected
  public function exec()
  {
    return $this->actionHandler();
  }

  public function output()
  {
    if ( PHP_SAPI == 'cli' )
    {
      $template = Template::getInstance();
      $template->load( $this->template() );
      $template->assign( 'title', $this->title() );
      $template->assign( 'content', $this->content() );

      $template->fetch();

      print_r( $template->data() );

      return;
    }

    Http::sendHeaders( $this->status(), $this->altContentType() );

    switch( $this->status() )
    {
      case 301:
      case 302:
      case 303:
        Router::redirect( $this->content(), $this->status() );
        return;
    }

    if ( isset($_REQUEST['dialog']) || !$this->template() )
    {
      echo $this->content();
      return;
    }

    $template = Template::getInstance();

    $templateFile = $template->file( $this->template() );
    if ( !file_exists($templateFile) )
    {
      Log::error( "cannot render template file '$templateFile': file not found" );
      echo "ERROR: cannot render template file '$templateFile': file not found";
      return;
    }

    $extension = StorageItem::getExtension ( $templateFile );

    switch( $extension )
    {
      case 'tpl':
        include_once $templateFile;
        break;

      case 'tpl2':
        $template->load( $this->template );

        $template->assign( 'title', $this->title() );
        $template->assign( 'content', $this->content() );

        echo $template->content();
        break;

      case 'php':
        include_once $templateFile;
        break;
    }
  }

  public function status()
  {
    return isset($this->subController) ? $this->subController->status() : $this->status;
  }

  public function altContentType()
  {
    return isset($this->subController) ? $this->subController->altContentType() : $this->altContentType;
  }

  public function title()
  {
    return isset($this->subController) ? $this->subController->title() : $this->title;
  }

  public function template()
  {
    return isset($this->subController) ? $this->subController->template() : $this->template;
  }

  public function content()
  {
    return isset($this->subController) ? $this->subController->content() : $this->content;
  }

}
