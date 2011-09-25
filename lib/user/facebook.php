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
  protected function connect()
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
      Log::debug( "connecting facebook api with stored token" );
      $this->token = @unserialize($this->token);
      $this->api->setSession($this->token);
    }
    else if ( isset($_GET['session']) )
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
    if ( !$this->id = $this->detectLoginSession() )
    {
      $cookie = $this->cookie();
      if ( $cookie )
        $this->id = $cookie['uid'];
    }

    Log::debug( "id -> ". $this->id );
  }

  public function authenticate()
  {
  }

  protected function detectLoginSession()
  {
    // Only detect a session when a login request has been made explicitely.
    // Otherwise, the Facebook API will still recognise the session even
    // after clearing the local cookie, effectively making it impossible to
    // logout.

    // @warning This might break permission updates, as those probably do
    // require fetching the new sesion token.
    if ( !isset($_GET['session']) )
      return;

    if ( !$this->api )
    {
      $this->api = new Facebook( array(
        'appId'  => Config::$facebookApp,
        'secret' => Config::$facebookSecret,
        'cookie' => true
        ) );
    }

    $session = $this->api->getSession();
    if ( !$session )
      return 0;

    if ( !isset($session['uid']) )
      return 0;

    $this->id = $session['uid'];
    $this->token = serialize($session);
    return $session['uid'];
  }

  // Returns entire verified cookie as array, or null if not valid or no cookie present
  protected function cookie()
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

  public function post( $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

/*
    if ( !$this->authenticated || !$this->api )
    {
      $result->error = "Facebook user not authenticated.";
      return $result;
    }
*/

    $attachment = array(
      'message' => $msg,
      'link' => $link, 
      'name' => $name,
      'caption' => $caption,
      'description' => $description,
      'picture' => $picture,
      'privacy' => json_encode( array('value' => 'ALL_FRIENDS') )

      // @todo allow choice of EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS, SELF.
    );

    Log::debug( "attachment: ". print_r( $attachment, true ) );
    try
    {
      $fbRs = $this->api->api('/me/feed', 'post', $attachment);

      $qPost = $this->db->escape( serialize($attachment) );
      $qResponse = $this->db->escape( serialize($fbRs) );
      $q = "insert into social_updates (ctime,network,post,response) values (now(), 'facebook', '$qPost', '$qResponse')";
      $this->db->query($q);
    }
    catch ( FacebookApiException $e )
    {
      $result->error = $e;
      Log::debug( "error: $result->error" );
      return $result;
    }

    if ( isset($fbRs['id']) )
    {
      $result->id = $fbRs['id'];
      list( $uid, $postId ) = explode( "_", $fbRs['id']);
      $result->url = "http://www.facebook.com/$uid/posts/$postId";
    }
    else
    {
      $result->error = $fbRs;
      Log::debug( "error: $result->error" );
    }
    
    return $result;
  }

  public function hasPerm( $perm, $verify=false )
  {
    if ( !$this->api )
      return false;

    // @fixme this is awfully slow (as expected from remote calls),
    // implement verify and only do a remote check prior to posting.  Or
    // just catch the error and always check the local store.
    try {
      $perm = $this->api->api( array( 'method' => 'users.hasapppermission', 'ext_perm' => $perm ) );
      return $perm;
    }
    catch( FacebookApiException $e )
    {
      Log::debug( "error in fb api call for hasPerm ($e), guessing user doesn't have it then" );
      // @todo should then be unlinked for this user..
      return false;
    }
  }
  
  public function getLoginUrl( $params )
  {
    return $this->api->getLoginUrl( $params );
  }
              
}

?>
