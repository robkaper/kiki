<?

class Social
{
  public static function fbPublish( &$fb, $msg, $link='', $name='', $caption='', $description = '', $picture = '' )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

    $attachment = array(
      'message' => $msg,
      'link' => $link, 
      'name' => $name,
      'caption' => $caption,
      'description' => $description,
      'picture' => $picture ? $picture : ( 'http://'. $_SERVER['SERVER_NAME']. Config::$headerLogo )
    );

    Log::debug( "fbPublish: ", print_r( $attachment, true ) );
    $fbRs = $fb->api('/me/feed', 'post', $attachment);
    Log::debug( "fbRs: ". print_r( $fbRs, true ) );

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

  public static function twPublish( &$tw, $msg )
  {
    $result = new stdClass;
    $result->id = null;
    $result->url = null;
    $result->error = null;

    Log::debug( "twPublish: $msg" );
    $twRs = $tw->post( 'statuses/update', array( 'status' => $msg ) );
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
