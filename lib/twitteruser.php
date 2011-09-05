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

    if ( $this->id )
      Log::debug( "TwitterUser->identify $id -> $this->id (mustVerify: $this->mustVerify)" );

    $this->load( $this->id );
  }

  public function authenticate()
  {
    $this->identify();

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
      list( $twUserId, $twSig ) = explode( ":", $_COOKIE['twitter_anywhere_identity'] );
      $hexDigest = sha1($twUserId. Config::$twitterSecret);
      $valid = ($twSig == $hexDigest);
      return $valid ? $twUserId : 0;
    }
    return 0;
  }

  public function registerAuth()
  {
    // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    $connection = new TwitterOAuth( Config::$twitterApp, Config::$twitterSecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );
    if ( !$connection )
    {
      Log::error( "TwitterUser->registerAuth failed, no connection" );
      return null;
    }

    $accessToken = $connection->getAccessToken($_REQUEST['oauth_verifier']);
    $twApiUser = $connection ? $connection->get('account/verify_credentials') : null;

    $qId = $this->db->escape( $accessToken['user_id'] );
    $qAccessToken = $this->db->escape( $accessToken['oauth_token'] );
    $qSecret = $this->db->escape( $accessToken['oauth_token_secret'] );
    $qName = $twApiUser ? $this->db->escape( $twApiUser->name ) : "";
    $qScreenName = $this->db->escape( $accessToken['screen_name'] );
    $qPicture = $twApiUser ? $this->db->escape( $twApiUser->profile_image_url ) : "";
    if ( $qId )
    {
      $q = "insert into twitter_users (id,ctime,mtime,access_token,secret,name,screen_name,picture) values( $qId, now(), now(), '$qAccessToken', '$qSecret', '$qName', '$qScreenName', '$qPicture') on duplicate key update access_token='$qAccessToken', secret='$qSecret', name='$qName', screen_name='$qScreenName', picture='$qPicture'";
      Log::debug( "TwitterUser->registerAuth q: $q" );
      $this->db->query($q);
    }

    return $accessToken;
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

    Log::debug( "TwitterUser->post: $msg" );
    $twRs = $this->tw->post( 'statuses/update', array( 'status' => $msg ) );

    $qPost = $this->db->escape( $msg );
    $qResponse = $this->db->escape( serialize($twRs) );
    $q = "insert into social_updates (ctime,network,post,response) values (now(), 'twitter', '$qPost', '$qResponse')";
    $this->db->query($q);

    if ( !$twRs )
    {
      $result->error = "Twitter status update failed.";
      return $result;
    }

    if ( isset($twRs->error) )
    {
      $result->error = $twRs->error;
      Log::debug( "twPost error: $result->error" );
    }
    else
    {
      $result->id = $twRs->id;
      $result->url = "http://www.twitter.com/". $twRs->user->screen_name. "/status/". $result->id;
    }
    
    return $result;
  }
}

?>
