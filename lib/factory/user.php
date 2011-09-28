<?

class Factory_User
{
  static public function getInstance( $service, $id=0, $kikiUserId = 0 )
  {
    // FIXME: class_exists
    return new $service( $id, $kikiUserId );
  }
}

?>
