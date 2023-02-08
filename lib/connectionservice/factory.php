<?php

namespace Kiki\ConnectionService;

use Kiki\Log;

class Factory
{
  static public function getInstance( $service )
  {
    $class = ucfirst($service);
    if ( !strstr($class, __NAMESPACE__) )
      $class = __NAMESPACE__. "\\". $class;

    if ( !class_exists($class) )
    {
      Log::error( "Non-existant class $class requested" );
      return null;
    }

    return new $class;
  }
}
