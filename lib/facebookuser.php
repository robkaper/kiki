<?

// @deprecated, port remaining stuff here to where it User_Facebook

class FacebookUser
{
  private $db;
  public $fb;

  public $id, $accessToken, $name, $authenticated;

  public function __construct( $id = null )
  {
  }


  public function createEvent( $title, $start, $end, $location, $description )
  {
    if ( !$this->authenticated || !$this->fb )
    {
      echo "not authenticated\n";
      return false;
    }


    // @fixme times must be EDT (Facebook time)
    // Privacy types: OPEN, CLOSED, SECRET
    $event_info = array(
      "privacy_type" => "OPEN",
      "name" => "Facebook/CMS koppeling Testje",
      "host" => "Me",
      "start_time" => $start,
      "end_time" => $end,
      "location" => $location,
      "description" => $description
    );

    return;
    
    // @todo add photo and invite support

    //Path to photo (only tested with relative path to same directory)
    // $file = "end300.jpg";
    // The key part - The path to the file with the CURL syntax
    // $event_info[basename($file)] = '@' . realpath($file);

    // $facebook->setFileUploadSupport(true);
    // $attachment = array( 'message' => $caption );    
    // $attachment[basename($file_path)] = '@' . realpath($file_path);
    // $result = $facebook->api('me/photos','post',$attachment);

    // print_r( $event_info );
    // var_dump($this->fb->api('me/events','post',$event_info));
    // var_dump($facebook->api("$pageId/events", 'post', $event_info));

    // $fb->api( array(
    //   'method' => 'events.invite',
    //   'eid' => $event_id,
    //   'uids' => $id_array,
    //   'personal_message' => $message,
    // ) );
  }

  public function storePerm( $perm )
  {
    $qPerm = $this->db->escape( $perm );
    $q = "insert into facebook_user_perms (facebook_user_id, perm_key, perm_value) values ($this->id, '$qPerm', 1)";
    $this->db->query($q);
  }

  public function revokePerm( $perm )
  {
    if ( !$this->fb )
      return;

    // Tell Facebook to revoke permission
    $fbRs = $this->fb->api( array( 'method' => 'auth.revokeExtendedPermission', 'perm' => $perm ) );

    // Remove permission from database
    $qPerm = $this->db->escape( $perm );
    $q = "update facebook_user_perms set perm_value=0 where perm_key='$qPerm' and facebook_user_id=$this->id";
    $this->db->query($q);

    // Remove user access_token and cookie to force retrieval of a new access token with correct permissions
    $q = "update facebook_users set access_token=null where id=$this->id";
    $this->db->query($q);

    $cookieId = "fbs_". Config::$facebookApp;
    setcookie( $cookieId, "", time()-3600, "/", $_SERVER['SERVER_NAME'] );
  }

}

?>
