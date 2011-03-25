<?

include_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class TwitterUser
{
  private $db;
  public $tw;

  public $id, $accessToken, $name, $authenticated;
  public $secret, $screenName, $picture;

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
  }

  public function load( $id )
  {
    Log::debug( "TwitterUser->load $id" );
      
    $qId = $this->db->escape( $id );
    $q = "select access_token, secret, name, screen_name, picture from twitter_users where id='$qId' and secret is not null";
    $rs = $this->db->query($q);
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

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
      $this->id = $this->cookie();

    Log::debug( "TwitterUser->identify $id -> ". $this->id );

    $this->load( $this->id );
  }

  // FIXME: Only use verify when post permissions are required (make a
  // second call when needed).  We ordinarily ignore verify_credentials
  // because the request takes a painful ~800ms, check if @Anywhere with
  // json-update callback could fix this later in the event queue
  public function authenticate( $verify = false )
  {
    Log::debug( "TwiterUser->authenticate" );
    if ( !$this->id )
        return;

    if ( !$this->accessToken )
      return;

    $this->tw = new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->accessToken, $this->secret);
    $this->authenticated = true;

    if ( !$verify || !$this->tw )
      return;

    $twApiUser = $this->tw->get('account/verify_credentials');
    if ( $twApiUser )
    {
      if (isset($twApiUser->error) )
      {
         Log::debug( "TwiterUser->authenticate error: $twApiUser->error, id: $this->id" );
         return;
      }
      // FIXME: inspect twApiUser to check if user is verified
      // $this->verified = true;
    }
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
}

?>