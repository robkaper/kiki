<?

class UserFactory
{
  static public function getInstance( $service, $id=0, $kikiUserId = 0 )
  {
//    $classFile = "user/".  strtolower($service). ".php";
//    if ( include_once($classFile) )
//    {
//      $class = "User_". ucfirst($service);
      return new $service( $id, $kikiUserId );
//    }
    return null;
  }
}

?>
