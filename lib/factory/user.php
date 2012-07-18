<?

class Factory_User
{
  static public function getInstance( $service, $id=0, $kikiUserId = 0 )
  {
    if ( !class_exists($service) )
    {
      Log::error( "Non-existant Class $class requested" );
      return null;
    }

    return new $service( $id, $kikiUserId );
  }
}

?>
