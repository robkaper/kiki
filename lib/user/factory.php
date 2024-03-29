<?php

namespace Kiki\User;

class Factory
{
  static public function getInstance( $service, $id=0, $kikiUserId = 0 )
  {
    $class = ucfirst($service);
    if ( !strstr($class, __NAMESPACE__) )
      $class = __NAMESPACE__. "\\". $class;

    if ( !class_exists($class) )
    {
      \Kiki\Log::error( "Non-existant class $class requested" );
      return null;
    }

    return new $class( $id, $kikiUserId );
  }
}
