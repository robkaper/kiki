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
  protected $data = [];
  protected $content = null;

  // Controller specific notices, warnings and errors. Merged with template data in data()
  protected $notices = null;
  protected $warnings = null;
  protected $errors = null;

  protected $subController = null;
  protected $actionMethod = null;

  public function __construct()
  {
    $this->notices = array();
    $this->warnings = array();
    $this->errors = array();
  }

  protected function initTemplateData()
  {
    $user = Core::getUser();

    $parsedUri = parse_url( $_SERVER['REQUEST_URI'] );
    $path = $parsedUri['path'] ?? null;

    // Assign core data to Kiki namespace.
    $data = [];
    $data[strtolower(__NAMESPACE__)] = [
      'server' => [
        'name' => $_SERVER['SERVER_NAME'] ?? null,
      ],
      'http' => [
        'get' => $_GET ?? null,
        'post' => $_POST ?? null,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'requestUri' => $_SERVER['REQUEST_URI'] ?? null,
        'path' => $path,
      ],
      'date' => [
        'year' => date('Y'),
      ],
      'user' => $user ? $user->templateData() : null,
    ];
    $data['legacy'] = Core::getTemplateData();

    $this->data = array_merge( $this->data, $data );
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

  public function class()
  {
    return $this->subController ? $this->subController->class() : get_called_class();
  }

  public function type()
  {
    return $this->subController ? $this->subController->type() : ClassHelper::classToType( get_called_class() );
  }

  public function method()
  {
    return $this->subController ? $this->subController->method() : $this->actionMethod;
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
    $this->initTemplateData();

    $remainder = null;

    $urlParts = parse_url($this->action ?? $this->objectId);
    if ( isset($urlParts['path']) && !empty($urlParts['path']) )
    {
      $pathParts = explode( '/', $urlParts['path'] );
      $action = array_shift($pathParts);
      $this->actionMethod = str_replace( '-', '_', $action ). 'Action';

      $remainder = implode( '/', $pathParts );
    }
    else
    {
      $action = 'index';
      $this->actionMethod = 'indexAction';
      $remainder = $this->objectId;
    }

    // Log::debug( sprintf( "class: %s, action: %s, this->action: %s, context: %s, remainder: %s, actionMethod: %s", get_called_class(), $action, $this->action, $this->context, $remainder, $this->actionMethod) );

    if ( $this->subController = $this->getActionController($action) )
    {
      $this->subController->setContext($this->context);
      $this->subController->setAction($remainder);

      // Log::debug( sprintf( "CALLING SUB class: %s, action: %s, this->action: %s, context: %s, remainder: %s, actionMethod: %s", get_called_class(), $action, $this->action, $this->context, $remainder, $this->actionMethod) );

      return $this->subController->actionHandler();
    }

   // Log::debug( sprintf( "NO SUB, CALLING METHOD class: %s, action: %s, this->action: %s, context: %s, remainder: %s, actionMethod: %s", get_called_class(), $action, $this->action, $this->context, $remainder, $this->actionMethod) );

    if ( !method_exists($this, $this->actionMethod) )
      return false;

    $actionMethod = $this->actionMethod;
    $ret = $this->$actionMethod($remainder);
    if  ( $ret )
    {
      // Fallbacks for when template doesn't do it
      if ( $this->status == 404 )
        $this->status = 200;

      if ( !$this->title )
        $this->title = $this->actionMethod;

      if ( $this->template == 'pages/404' )
        $this->template = 'pages/default';
    }

    Log::debug( sprintf( "RETURNING class: %s, action: %s, this->action: %s, context: %s, remainder: %s, actionMethod: %s", get_called_class(), $action, $this->action, $this->context, $remainder, $this->actionMethod) );

    return $ret;
  }

  public function getActionController($action)
  {
    $actionController = get_class($this). "\\". ucfirst($action);

    if ( class_exists($actionController) )
      return new $actionController();

    return null;
  }

  // Exists because actionHandler is protected
  final public function exec()
  {
    return $this->actionHandler();
  }

  // Called in succession to exec.  One use-case would be to add timers to
  // the template after execution.
  public function postExec()
  {
  }

  public function output()
  {
    if ( PHP_SAPI == 'cli' )
    {
      $template = Template::getInstance();
      $template->load( $this->template() );

      foreach( $this->data() as $key => $data )
        $template->assign( $key, $data );
      $template->assign( 'title', $this->title() );
      $template->assign( 'content', $this->content() );

      $template->fetch();

      print_r( $template->data() );

      return;
    }

    Http::sendHeaders( $this->status(), $this->altContentType() );

    // Log::debug( sprintf( "status: %s, title: %s, template: %s", $this->status(), $this->title(), $this->template() ) );

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
        $template->load( $this->template() );

        foreach( $this->data() as $key => $data )
          $template->assign( $key, $data );

        $template->assign( 'title', $this->title() );
        $template->assign( 'content', $this->content() );

        echo $template->content();
        break;

      case 'php':
        include_once $templateFile;
        break;
    }
  }

  public function data()
  {
    if ( isset($this->subController) )
      $this->data = array_merge( $this->data, $this->subController->data() );

    $this->data['notices'] = array_merge( $this->notices, $this->data['notices'] ?? array() );
    $this->data['warnings'] = array_merge( $this->warnings, $this->data['warnings'] ?? array() );
    $this->data['errors'] = array_merge( $this->errors, $this->data['errors'] ?? array() );

    return $this->data;
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
