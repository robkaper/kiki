<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class TwitterUser
{
  private $db;
  public $tw;

  public $id, $accessToken, $name, $authenticated;
  public $secret, $screenName, $picture;
  private $mustVerify;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    $this->tw = null;

    $this->reset();
    
    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->accessToken = "";
    $this->secret = "";
    $this->name = "";
    $this->screenName = "";
    $this->picture = "";
    $this->authenticated = null;
    $this->mustVerify = true;
  }

  public function load( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select access_token, secret, name, screen_name, picture from twitter_users where id='$qId' and secret is not null";
    $rs = $this->db->query($q);
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    Log::debug( "TwitterUser->load $id" );

    $this->id = $id;
    $this->accessToken = $o->access_token;
    $this->secret = $o->secret;
    $this->name = $o->name;
    $this->screenName = $o->screen_name;
    $this->picture = $o->picture;
  }

  public function identify( $id = 0 )
  {
    if ( $id )
      $this->id = $id;
    else
    {
      $this->id = $this->cookie();
      $this->mustVerify = false;
    }

    Log::debug( "TwitterUser->identify $id -> $this->id (mustVerify: $this->mustVerify)" );

    $this->load( $this->id );
  }

  public function authenticate()
  {
    if ( !$this->id )
        return;

    if ( !$this->accessToken )
      return;

    $this->tw = new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->accessToken, $this->secret);

    if ( !$this->tw )
      return;

    if ( $this->mustVerify )
    {
      $twRs = $this->tw->get( "account/verify_credentials" );
      Log::debug( "TwiterUser->authenticate: account/verify_credentials: ". print_r( $twRs, true ) );
      if ( isset($twRs['error']) )
        return;
      $this->mustVerify = false;
    }
    else
      Log::debug( "TwitterUser->authenticate: trusted session, didn't verify credentials" );

    $this->authenticated = true;
  }

  private function cookie()
  {
    if ( array_key_exists( 'twitter_anywhere_identity', $_COOKIE ) )
    {
      list( $twUserId, $twSig ) = split( ":", $_COOKIE['twitter_anywhere_identity'] );
      $hexDigest = sha1($twUserId. Config::$twitterSecret);
      $valid = ($twSig == $hexDigest);
      return $valid ? $twUserId : 0;
    }
    return 0;
  }

  private function registerAuth()
  {
  }

  public function post( $msg )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

    if ( !$this->authenticated || !$this->tw )
    {
      $result->error = "Twitter user not authenticated.";
      return $result;
    }

    Log::debug( "TwitterUser->publish: $msg" );
    $twRs = $this->tw->post( 'statuses/update', array( 'status' => $msg ) );
    Log::debug( "twRs: ". print_r( $twRs, true ) );

    if ( isset($twRs->error) )
      $result->error = $twRs->error;
    else
    {
      $result->id = $twRs->id;
      $result->url = "http://www.twitter.com/". $twRs->user->screen_name. "/status/". $result->id;
    }
    
    return $result;
  }
}

?>