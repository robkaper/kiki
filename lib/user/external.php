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
  protected $externalId = 0;
  protected $kikiUserIds = array();

  protected $name = null;
  protected $screenName = null;
  protected $picture = null;

  protected $connected = false;
  protected $authenticated = false;

  public function __construct( $id=0, $kikiUserId = 0 )
  {
    $this->db = $GLOBALS['db'];	

    if ( $this->externalId = $id )
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
  abstract protected function post( $objectId, $msg, $link='', $name='', $caption='', $description = '', $picture = '' );
  abstract protected function postArticle( &$article );
  abstract protected function createEvent( $objectId, $title, $start, $end, $location, $description, $picture=null );

  public function api()
  {
    if ( !$this->api )
      $this->connect();

    if ( !$this->api )
    {
      throw new UserApiException( 'External user API for '. $this->serviceName(). ' called but not available' );
    }

    return $this->api;
  }

  public function id()
  {
    return $this->id;
  }

  public function externalId()
  {
    return $this->externalId;
  }
  
  public function serviceName()
  {
    return preg_replace( "/^User_/", "", get_class($this) );
  }
  
  public function uniqId()
  {
    return get_class($this). '_'. $this->externalId;
  }

  public function setName( $name ) { $this->name = $name; }

  public function name()
  {
    return $this->name;
  }

  public function setScreenName( $screenName ) { $this->screenName = $screenName; }

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

    $q = $this->db->buildQuery( "select user_id from connections where service='%s' and external_id='%s'", get_class($this), $this->externalId );
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
    if ( $kikiUserId )
      $q = $this->db->buildQuery( "select id, token, secret, name, screenname, picture from connections where service='%s' and external_id=%d and user_id=%d", get_class($this), $this->externalId, $kikiUserId );
    else
      $q = $this->db->buildQuery( "select id, token, secret, name, screenname, picture from connections where service='%s' and external_id=%d", get_class($this), $this->externalId );
    
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->kikiUserIds[] = $kikiUserId;

    $this->id = $o->id;
    $this->token = $o->token;
    $this->secret = $o->secret;
    $this->name = $o->name;
    $this->screenName = $o->screenname;
    $this->picture = $o->picture;
  }

  public function link( $kikiUserId )
  {
    // If we have a local user to link to, delete anonymous links.
    // FIXME: update the current connection, don't make a new one. (when comments etc link to our id instead of external_id)
    if ( $kikiUserId )
      $this->unlink();

    $this->kikiUserIds[] = $kikiUserId;
    $q = $this->db->buildQuery( "insert into connections( user_id, external_id, service, ctime, mtime, token, secret, name, screenname, picture ) values ( %d, %d, '%s', now(), now(), '%s', '%s', '%s', '%s', '%s' )", $kikiUserId, $this->externalId, get_class($this), $this->token, $this->secret, $this->name, $this->screenName, $this->picture );
    $rs = $this->db->query($q);
  }
  
  public function unlink( $kikiUserId = 0 )
  {
    if ( $kikiUserId )
      $q = $this->db->buildQuery( "delete from connections where user_id=%d and external_id=%d", $kikiUserId, $this->externalId );
    else
      $q = $this->db->buildQuery( "delete from connections where external_id=%d", $this->externalId );

    $rs = $this->db->query($q);
  }
}

?>
