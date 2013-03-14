<?php

/**
 * Class providing controller factory and base class.
 *
 * @todo Split into a proper base class abstract and factory.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Controller
{
  protected $instanceId = 0;
  protected $objectId = 0;

  protected $status = 404;
  protected $title = '404 Not found';
  protected $template = 'pages/404';
  protected $content = null;

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
    $className = ClassHelper::typeToClass($type);

		if ( !class_exists($className) )
		{
			$classFile = ClassHelper::classToFile($className);
			Log::error( "class $className not defined in $classFile" );
			return new Controller();
		}

    return new $className;
  }

  public function setInstanceId( $instanceId )
  {
    $this->instanceId = $instanceId;
  }

  public function setObjectId( $objectId )
  {
    $this->objectId = $objectId;
  }

  public function exec() {}

  public function status()
  {
    return isset($this->subController) ? $this->subController->status() : $this->status;
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