<?

class Controller_Pages extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    Log::debug( print_r($this,true) );

    if ( !$this->objectId )
    {
      $this->status = 200;
      $this->template = 'pages/default';
      $this->content = '// TODO: Index of ...';
      return;
    }

    // Find page under this section through subcontroller.
    // TODO: also find subsections...
    if ( $handler = Router::findPage( $this->objectId, $this->instanceId ) )
    {
      $this->subController = Controller::factory($handler->type);
      $this->subController->setInstanceId($handler->instanceId);
      $this->subController->setObjectId($handler->remainder);
      $this->subController->exec();
    }
  }
}
  
?>