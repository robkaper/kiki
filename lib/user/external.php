<?

/**
 * Abstract class to deal with users from external sources.
 *
 * This class has to be extended to specify each external platform's
 * specific identification, authentication and communication services. 
 * Local functionality such as storage is standardised and the abstract
 * methods ensure a unified local interface against external users.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class UserApiException extends Exception {}
 
abstract class User_External
{
  protected $db;

  protected $api = null;

  protected $token = null;
  protected $secret = null;
  
  protected $id = 0;
  protected $kikiUserIds = array();

  protected $name = null;
  protected $screenName = null;
  protected $picture = null;

  protected $connected = false;
  protected $authenticated = false;

  public function __construct( $id=0, $kikiUserId = 0 )
  {
    $this->db = $GLOBALS['db'];	

    if ( $this->id = $id )
    {
      $this->load($kikiUserId);
      if ( !$kikiUserId )
        $this->loadKikiUserIds();
    }
    else
    {
      $this->identify();
      $this->loadKikiUserIds();
    }
  }

  abstract protected function connect();
  abstract public function identify();
  abstract public function authenticate();
  abstract protected function cookie();
  abstract protected function detectLoginSession();
  // abstract public function getLoginUrl();
  abstract protected function post( $msg, $link='', $name='', $caption='', $description = '', $picture = '' );
  abstract protected function postArticle( &$article );
  abstract protected function createEvent( $title, $start, $end, $location, $description, $picture=null );

  public function api()
  {
    if ( !$this->api )
      $this->connect();

    if ( !$this->api )
    {
      throw new UserApiException( 'External user API for '. $this->servericeName(). ' called but not available' );
    }

    return $this->api;
  }

  public function id()
  {
    return $this->id;
  }

  public function serviceName()
  {
    return preg_replace( "/^User_/", "", get_class($this) );
  }
  
  public function uniqId()
  {
    return get_class($this). '_'. $this->id;
  }

  public function setName( $name ) { $this->name = $name; }

  public function name()
  {
    return $this->name;
  }

  public function screenName()
  {
    return $this->screenName;
  }

  public function setPicture( $picture ) { $this->picture = $picture; }

  public function picture()
  {
    return $this->picture;
  }

  public function token()
  {
    return $this->token;
  }

  private function loadKikiUserIds()
  {
    $this->kikiUserIds = array();

    $q = $this->db->buildQuery( "select user_id from users_connections where service='%s' and external_id='%s'", get_class($this), $this->id );
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numrows($rs) )
      while( $o = $this->db->fetchObject($rs) )
      {
        if ( $o->user_id )
          $this->kikiUserIds[] = $o->user_id;
      }

   // $this->kikiUserIds = array_unique( $this->kikiUserIds );
  }

  public function kikiUserIds()
  {
    return $this->kikiUserIds;
  }

  public function kikiUserId()
  {
    $n = count($this->kikiUserIds);
    switch($n)
    {
      case 0:
        return 0;
      case 1:
        return $this->kikiUserIds[0];
      default:
        return false;
    }
  }

  private function load( $kikiUserId )
  {
    $q = $this->db->buildQuery( "select token, secret, name, screenname, picture from users_connections where service='%s' and external_id=%d and user_id=%d", get_class($this), $this->id, $kikiUserId );
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->kikiUserIds[] = $kikiUserId;

    $this->token = $o->token;
    $this->secret = $o->secret;
    $this->name = $o->name;
    $this->screenName = $o->screenname;
    $this->picture = $o->picture;
  }

  public function link( $kikiUserId )
  {
    $this->kikiUserIds[] = $kikiUserId;
    $q = $this->db->buildQuery( "insert into users_connections( user_id, external_id, service, ctime, mtime, token, secret, name, screenname, picture ) values ( %d, %d, '%s', now(), now(), '%s', '%s', '%s', '%s', '%s' )", $kikiUserId, $this->id, get_class($this), $this->token, $this->secret, $this->name, $this->screenName, $this->picture );
    $rs = $this->db->query($q);
  }
  
  public function unlink( $kikiUserId = 0 )
  {
    if ( $kikiUserId )
      $q = $this->db->buildQuery( "delete from users_connections where user_id=%d and external_id=%d", $kikiUserId, $this->id );
    else
      $q = $this->db->buildQuery( "delete from users_connections where external_id=%d", $this->id );

    $rs = $this->db->query($q);
  }
}

?>
