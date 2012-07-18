<?

if ( isset(Config::$twitterOAuthPath) )
{
  require_once Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php"; 
}

class ConnectionService_Twitter
{
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('TwitterOAuth') )
      return;

    $this->enabled = true;
  }

  public function enabled() { return $this->enabled; }

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
