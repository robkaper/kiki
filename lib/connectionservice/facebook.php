<?

if ( isset(Config::$facebookSdkPath) )
{
  require_once Config::$facebookSdkPath. "/src/facebook.php"; 
}

class ConnectionService_Facebook
{
  private $api;

  public function __construct()
  {
    $this->api = new Facebook( array(
      'appId'  => Config::$facebookApp,
      'secret' => Config::$facebookSecret,
      'cookie' => false
      ) );
  }

  public function name()
  {
    return "Facebook";
  }
  
  public function loginUrl( $params )
  {
    return $this->api->getLoginUrl( $params );
  }
}

?>
