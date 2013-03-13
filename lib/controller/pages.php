<?php

class Controller_Pages extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    if ( !$this->objectId )
      $this->objectId = 'index';

    // Find page under this section through subcontroller.
    // TODO: also find subsections...
    if ( $this->subController = Router::findPage( $this->objectId, $this->instanceId ) )
    {
      $this->subController->exec();
    }
    else if ( $this->objectId == 'index' )
    {
/*
      $this->status = 200;
      $this->template = 'pages/default';
      $this->title = 'Index of section '. $this->instanceId;
      $this->content = "// TODO: show 'index' page or else a generated \"Index of ...\"";
*/
    }
  }
}
  
?>