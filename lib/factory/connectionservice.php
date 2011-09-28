<?

class Factory_ConnectionService
{
  static public function getInstance( $service )
  {
    $class = "ConnectionService_". ucfirst($service);
    // FIXME: class_exists
    return new $class;
  }
}

?>