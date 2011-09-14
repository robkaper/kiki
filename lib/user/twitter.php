<?

class User_Twitter extends User_External
{
  private $oAuthToken = null;

  public function connect()
  {
    // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    // @fixme this connects based on _SESSION, adjust to also allow connection based on stored token

    if ( isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret']) )
    {

      $this->token = $_SESSION['oauth_token'];
      $this->secret = $_SESSION['oauth_token_secret'];

      Log::debug( "connecting twitter api with session token and secret" );
      $this->api = new TwitterOAuth(Config::$twitterApp, Config::$twitterSecret, $this->token, $this->secret);

      Log::debug( "getting access token and secret from request verifier ". $_REQUEST['oauth_verifier'] );
      $this->oAuthToken = $this->api->getAccessToken( $_REQUEST['oauth_verifier'] );
      $this->token = $this->oAuthToken['oauth_token'];
      $this->secret = $this->oAuthToken['oauth_token_secret'];
    }
    else if ( $this->token && $this->secret )
    {
      Log::debug( "connecting twitter api with stored token and secret" );
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
    $this->id = $this->cookie();

    Log::debug( "id -> ". $this->id );
  }
  
  public function authenticate()
  {
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

}

?>
