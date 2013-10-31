<?php

namespace Kiki\ConnectionService;

class Factory
{
  static public function getInstance( $service )
  {
		// TODO: remove migration from namespaces
		$service = str_replace("Connection_", "", $service);

    $class = ucfirst($service);
		if ( !strstr($class, __NAMESPACE__) )
			$class = __NAMESPACE__. "\\". $class;

    if ( !class_exists($class) )
    {
      \Kiki\Log::error( "Non-existant class $class requested" );
      return null;
    }

    return new $class;
  }
}
