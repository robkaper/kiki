<?

class Factory_User
{
  static public function getInstance( $service, $id=0, $kikiUserId = 0 )
  {
    return new $service( $id, $kikiUserId );
  }
}

?>
