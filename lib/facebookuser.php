<?

class FacebookUser
{
  private $db;
  public $fb;

  public $id, $accessToken, $name, $authenticated;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    $this->fb = new Facebook( array(
      'appId'  => Config::$facebookApp,
      'secret' => Config::$facebookSecret,
      'cookie' => true
      ) );

    $this->reset();

    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {

    $this->id = 0;
    $this->accessToken = "";
    $this->name = "";
    $this->authenticated = null;
  }

  public function load( $id )
  {
    Log::debug( "FacebookUser->load $id" );

    $qId = $this->db->escape( $id );
    $q = "select access_token, name from facebook_users where id='$qId'";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $id;
    $this->accessToken = @unserialize($o->access_token);
    if( $this->accessToken )
      Log::debug( "FacebookUser->load has offline_access token!" );
    
    $this->name = $o->name;
  }

  public function identify( $id = 0 )
  {
    if ( $id )
      $this->id = $id;
    else
    {
      $cookie = $this->cookie();
      if ( $cookie )
        $this->id = $cookie['uid'];
    }

    Log::debug( "FacebookUser->identify $id -> ". $this->id );

    $this->load( $this->id );
  }

  public function authenticate()
  {
    Log::debug( "FacebookUser->authenticate" );
    if ( !$this->id )
        return;

    if ( $this->accessToken )
      $this->fb->setSession( $this->accessToken );

    $fbSession = $this->fb->getSession();
    if ( $fbSession )
    {
      try
      {
        $this->id = $this->fb->getUser();
        if ( !$this->id )
          return;

        if ( !$this->accessToken || $this->accessToken != $fbSession )
          $this->registerAuth();

        $this->authenticated = true;
        $this->load( $this->id );

        if ( array_key_exists( 'session', $_GET ) )
        {
          $this->registerAuth();

          // Redirect to avoid ?session= request to appear in Analytics etc
          header( "Location: ". $_SERVER['SCRIPT_URL'], true, 301 );
          exit();
        }
      }
      catch ( FacebookApiException $e )
      {
        error_log($e);
      }
    }

  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  private function cookie()
  {
    $args = array();

    $cookieId = "fbs_". Config::$facebookApp;
    if ( array_key_exists( $cookieId, $_COOKIE ) )
      parse_str( trim($_COOKIE[$cookieId], '\\"'), $args );\

    ksort($args);
    
    $payload = '';
    foreach ( $args as $key => $value )
      if ( $key != 'sig' )
        $payload .= $key. '='. $value;

    if ( !array_key_exists( 'sig', $args ) || md5($payload. Config::$facebookSecret) != $args['sig'] )
      return null;

    return $args;
  }

  private function registerAuth()
  {
    Log::debug( "FacebookUser->registerAuth" );

    $fbUser = $this->fb->api('/me');
    if ( !$fbUser )
    {
      Log::debug( "SNH: fbRegisterAuth failed, no fbUser" );
      return;
    }

    $fbSession = $this->fb->getSession();
    if ( $fbSession && $fbSession['expires'] == 0 )
    {
      $qAccessToken = $this->db->escape( serialize($fbSession) );
      Log::debug( "storing session access token (expires=0)" );
    }
    else
    {
      $qAccessToken = $this->accessToken;
      Log::debug( "storing existing access token, if any" );
    }

    $qId = $this->db->escape( $fbUser['id'] );
    $qName = $this->db->escape( $fbUser['name'] );
    $q = "insert into facebook_users (id,access_token,name) values( $qId, '$qAccessToken', '$qName') on duplicate key update access_token='$qAccessToken', name='$qName'";

    Log::debug( "fbRegisterAuth q: $q" );
    $this->db->query($q);
  }
  
  public function post( $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

    if ( !$this->authenticated || !$this->fb )
    {
      $result->error = "Facebook user not authenticated.";
      return $result;
    }

    $attachment = array(
      'message' => $msg,
      'link' => $link, 
      'name' => $name,
      'caption' => $caption,
      'description' => $description,
      'picture' => $picture
    );

    Log::debug( "fbPublish: ". print_r( $attachment, true ) );
    try
    {
      $fbRs = $this->fb->api('/me/feed', 'post', $attachment);
      Log::debug( "fbRs: ". print_r( $fbRs, true ) );
    }
    catch ( FacebookApiException $e )
    {
      $result->error = $e;
      return $result;
    }

    if ( isset($fbRs['id']) )
    {
      $result->id = $fbRs['id'];
      list( $uid, $postId ) = split( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
    }
    else
      $result->error = $fbRs;
    
    return $result;
  }
}

?>