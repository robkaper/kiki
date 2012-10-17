<?php

class Factory_ConnectionService
{
  static public function getInstance( $service )
  {
    $class = "ConnectionService_". ucfirst($service);
    if ( !class_exists($class) )
    {
      Log::error( "Non-existant Class $class requested" );
      return false;
    }

    return new $class;
  }
}

?>