<?

class Factory_ConnectionService
{
  static public function getInstance( $service )
  {
    $class = "ConnectionService_". ucfirst($service);
    return new $class;
  }
}

?>