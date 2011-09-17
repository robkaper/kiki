<?

class ConnectionService_Twitter
{
  public function name()
  {
    return "Twitter";
  }

  public function loginUrl()
  {
    return Config::$kikiPrefix. "/twitter-redirect.php";
  }
}

?>
