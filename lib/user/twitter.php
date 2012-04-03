<?

require_once( $GLOBALS['kiki']. '/lib/twitteroauth/twitteroauth.php');

class User_Twitter extends User_External
{
  private $oAuthToken = null;
  // private $oAuthVerifier = null;

  protected function connect()
  {
    // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    if ( $this->token && $this->secret )
    {
      $this->api = new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->token, $this->secret);
    }
    
    if ( !$this->api )
    {
      Log::debug( "failed, no connection" );
      return;
    }

    // $this->id = $this->oAuthToken['user_id'];
    $this->screenName = $this->oAuthToken['screen_name'];
  }

  public function identify()
  {
    if ( !$this->id = $this->detectLoginSession() )
      $this->id = $this->cookie();
  }
  
  public function authenticate()
  {
  }

  protected function detectLoginSession()
  {
    if ( !isset($_REQUEST['oauth_token']) )
      return 0;

    if ( !isset($_SESSION) )
      session_start();

    if ( !isset($_SESSION['oauth_token']) || !isset($_SESSION['oauth_token_secret']) )
      return 0;
      
    if ( $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
    {
      Log::error( "SNH: twitter oauth token mismatch" );
      return 0;
    }

    $this->token = $_SESSION['oauth_token'];
    $this->secret = $_SESSION['oauth_token_secret'];
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);

    Log::debug( "connecting twitter api with session token and secret" );
    $this->api = new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->token, $this->secret);

    Log::debug( "getting access token and secret from request verifier ". $_REQUEST['oauth_verifier'] );
    $this->oAuthToken = $this->api->getAccessToken( $_REQUEST['oauth_verifier'] );
    if ( !$this->oAuthToken )
      return 0;

    $this->token = $this->oAuthToken['oauth_token'];
    $this->secret = $this->oAuthToken['oauth_token_secret'];

    return $this->oAuthToken['user_id'];
  }

  protected function cookie()
  {
    if ( array_key_exists( 'twitter_anywhere_identity', $_COOKIE ) )
    {
      list( $id, $sig ) = explode( ":", $_COOKIE['twitter_anywhere_identity'] );
      $hexDigest = sha1($id. Config::$twitterSecret);
      $valid = ($sig == $hexDigest);
      return $valid ? $id : 0;
    }
    return 0;
  }

  public function loadRemoteData()
  {
    $data = $this->api ? $this->api->get('account/verify_credentials') : null;
    if ( !$data )
    {
      Log::debug( "failed, no data" );
      return;
    }

    $this->name = $data->name;
    $this->picture = $data->profile_image_url;
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
      $result->error = "Twitter user not authenticated.";
      return $result;
    }
*/

    Log::debug( "msg: $msg" );
    $twRs = $this->api->post( 'statuses/update', array( 'status' => $msg ) );

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

  public function postArticle( &$article )
  {
    $tinyUrl = TinyUrl::get( $article->url() );

    $msg = sprintf( "%s %s %s", $article->title(), $tinyUrl, $article->hashtags() );
    $result = $this->post( $msg );
    return $result;
  }

  public function postEvent( &$event )
  {
    $tinyUrl = TinyUrl::get( $event->url() );
    $msg = sprintf( "%s %s %s", $event->title(), $tinyUrl, $event->hashtags() );
    $result = $this->post( $msg );
    return $result;
  }

  public function createEvent( $title, $start, $end, $location, $description, $picture=null )
  {
    return null;
  }
  
}

?>
