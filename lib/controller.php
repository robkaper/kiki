<?

/**
* @class Controller
* Controller class. Sort of.
* @todo Make this a proper abstract to be reimplemented, plus a factory so
* router can select the correct handler.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Controller
{
  protected $instanceId = 0;
  protected $objectId = 0;

  protected $status = 404;
  protected $title = '404 Not found';
  protected $template = 'page/body-404';
  protected $content = null;

  public static function factory($type)
  {
    $classFile = "controller/".  strtolower($type). ".php";
    Log::debug( "factory $type, file: $classFile" );
    if ( include_once($classFile) )
    {
      Log::debug( "included $classFile" );
      // include_once "$classFile";
      $classname = "Controller_". ucfirst($type);
      return new $classname;
    }

    Log::debug( "returning standard controller" );
    return new Controller();
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
    return $this->status;
  }

  public function title()
  {
    return $this->title;
  }

  public function template()
  {
    return $this->template;
  }

  public function content()
  {
    return $this->content;
  }
}
  
?>