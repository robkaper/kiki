<?

/**
 * Facebook user class.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */
  
class User_Facebook extends User_External
{
  public function connect()
  {
    if ( Config::$facebookApp && extension_loaded('curl') )
    {
      $this->api = new Facebook( array(
        'appId'  => Config::$facebookApp,
        'secret' => Config::$facebookSecret,
        'cookie' => true
        ) );
    }

    if ( $this->id && $this->token )
    {
      Log::debug( "connecting facebook api with stored token (todo)" );

      // @fixme move to load..
      // $this->token = @unserialize($o->token);
      // $this->api->setSession($this->token);
    }
    else
    {
      Log::debug( "connecting facebook api with session" );
      $session = $this->api->getSession();
      if ( $session && $session['expires'] == 0 )
      {
        Log::debug( "endless token from session, need to link/store?" );
        $this->token = serialize($session);
      }
    }

    // @fixme debug and re-enable
    return;

    $perms = explode(",", $_GET['perms'] );
    foreach( $perms as $perm )
      self::storePerm($perm);
  } 

  public function identify()
  {
    $cookie = $this->cookie();
    Log::debug( "cookie: ". print_r( $cookie, true ) );
    if ( $cookie )
      $this->id = $cookie['uid'];
    else if ( $this->api )
    {
      $session = $this->api->getSession();
      Log::debug( "session: ". print_r( $session, true ) );
      if ( isset($session['uid']) )
        $this->id = $session['uid'];
    }

    Log::debug( "id -> ". $this->id );
  }
  
  public function authenticate()
  {
  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  private function cookie()
  {
    $args = array();

    $cookieId = "fbs_". Config::$facebookApp;
    if ( isset($_COOKIE[$cookieId]) )
      parse_str( trim($_COOKIE[$cookieId], '\\"'), $args );\

    ksort($args);
    
    $payload = '';
    foreach ( $args as $key => $value )
      if ( $key != 'sig' )
        $payload .= $key. '='. $value;

    if ( !isset($args['sig']) || md5($payload. Config::$facebookSecret) != $args['sig'] )
      return null;

    return $args;
  }

  // @todo load user_id, external_id, service, ctime, mtime, accesstoken, secret, name, screen, picture
  public function loadRemoteData()
  {
    $data = null;

    try
    {
      $data = $this->api ? $this->api->api('/me') : null;
    }
    catch ( FacebookApiException $e )
    {
      Log::error( "FacebookApiException: $e" );
    }

    if ( !$data )
    {
      Log::debug( "failed, no data" );
      return;
    }

    // $this->id = $data['id'];
    $this->name = $this->screenName = $data['name'];
    $this->picture = "http://graph.facebook.com/". $this->id. "/picture";
  }
}

?>
