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

  protected $contentType = 'html';

  protected $status = 404;
  protected $altContentType = null;
  protected $title = '404 Not found';
  protected $template = 'pages/404';
  protected $content = null;

  protected $errors = null;

  protected $subController = null;
  protected $extraScripts = null;
  protected $extraStyles = null;

  public function __construct()
  {
    $this->extraScripts = array();
    $this->extraStyles = array();
  }

  public static function factory($type)
  {
    $className = \Kiki\Config::$namespace. "\\". ClassHelper::typeToClass($type);

    if ( !class_exists($className) )
      $className = __NAMESPACE__. "\\". ClassHelper::typeToClass($type);

    if ( !class_exists($className) )
    {
      $classFile = ClassHelper::classToFile($className);
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

	public function actionHandler()
	{
	  $remainder = null;

	  if ( $this->action )
	  {
	    $actionMethod = $this->action. 'Action';
	    
	    // FIXME: not handling remainder. But maybe that's okay when the config has an explicit action.
	  }
	  else
	  {
            $urlParts = parse_url($this->objectId);
            if ( isset($urlParts['path']) && !empty($urlParts['path']) )
            {
			$pathParts = explode( '/', $urlParts['path'] );
			$action = array_shift($pathParts);
			$actionMethod = $action. 'Action';

			$remainder = preg_replace( "#^$action/?#", "", $this->objectId );
		}
		else
		{
			$actionMethod = 'indexAction';
			$remainder = $this->objectId;
		}
            }

		if ( !method_exists($this, $actionMethod) )
			return false;

		Log::debug( "found actionMethod: ". get_class($this). "->$actionMethod, remainder: $remainder" );

		// $this->status = 200;
		$this->title = $actionMethod;
		$this->template = 'pages/default';

		return $this->$actionMethod($remainder);
	}

  // FIXME: For overloading?? What did this do?
  public function exec()
  {
  }

  public function output()
  {
        $template = Template::getInstance();
    
        $template->load( $this->template() );

        \Kiki\Http::sendHeaders( $this->status(), $this->altContentType() );

        include_once $template->file( $this->template() );

        return;
        
		if ( PHP_SAPI == 'cli' )
		{
			$template = Template::getInstance();
			$template->assign( 'footerText', Boilerplate::copyright() );
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
        break;

      default:

				if ( isset($_REQUEST['dialog']) || !$this->template() )
				{
					echo $this->content();
					return;
				}

        $template = Template::getInstance();
        $template->assign( 'footerText', Boilerplate::copyright() );
    
        $template->load( $this->template() );

        $template->assign( 'title', $this->title() );
        $template->assign( 'content', $this->content() );

        echo $template->content();

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
  
?>