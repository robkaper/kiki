<?

if ( isset(Config::$facebookSdkPath) )
{
  require_once Config::$facebookSdkPath. "/src/facebook.php"; 
}

class ConnectionService_Facebook
{
  private $api;
  private $enabled = false;

  public function __construct()
  {
    if ( !class_exists('Facebook') )
      return;

    $this->api = new Facebook( array(
      'appId'  => Config::$facebookApp,
      'secret' => Config::$facebookSecret,
      'cookie' => false
      ) );

    $this->enabled = true;
  }

  public function enabled() { return $this->enabled; }

  public function name()
  {
    return "Facebook";
  }
  
  public function loginUrl( $params = null )
  {
    if ( !$params )
      $params = array();
    else if ( !is_array($params) )
    {
      Log::debug( "called with non-array argument" );
      $params = array();
    }
    // $params['display'] = "popup";

    return $this->api ? $this->api->getLoginUrl($params) : null;
  }
}

?>
