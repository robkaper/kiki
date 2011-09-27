<?

/**
 * Class for comments attached to objects.
 *
 * @fixme ObjectId's aren't really object ID's yet and reference article
 * ID's.  Comments should be Objects themselves and the reference should be
 * done by referenceId or something like that.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Comments
{
  public static function form( &$user, $objectId )
  {
    $content = "<div id=\"commentForm_$objectId\" class=\"jsonupdate shrunk\">\n";
    $content .= $user->anyUser() ? Boilerplate::commentForm($user, $objectId) : Boilerplate::commentLogin();
    $content .= "</div>\n";
    return $content;
  }

  // FIXME: port to users_connections
  public static function show( &$db, &$user, $objectId, $jsonLast=null )
  {
    $comments = array();

    $qObjectId = $db->escape( $objectId );
    $qLast = $jsonLast ? ("and c.id>". $db->escape($jsonLast)) : "";
    $q = "select c.id,c.body,c.ctime, u.facebook_user_id, u.twitter_user_id from comments c, users u where c.object_id=$qObjectId and c.user_id=u.id $qLast order by ctime asc";
    $rs = $db->query($q);
    if ( $rs && $db->numrows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        if ( $o->facebook_user_id )
        {
          $type = "facebook";
          $name = $db->getSingleValue( "select name from facebook_users where id=$o->facebook_user_id" );
          $pic = "http://graph.facebook.com/". $o->facebook_user_id. "/picture";
        }
      else if ( $o->twitter_user_id )
      {
        $type = "twitter";
        $name = $db->getSingleValue( "select name from twitter_users where id=$o->twitter_user_id" );
        $pic = $db->getSingleValue( "select picture from twitter_users where id=$o->twitter_user_id" );
      }
      else
      {
        $type = "unknown";
        $name = "name";
      }

      $comments[] = Comments::showSingle( $objectId, $o->id, $name, $pic, $type, $o->body, $o->ctime );
    }
  }
  else if ( $jsonLast===null )
    $comments[] = Comments::showDummy( $objectId );

  if ( $jsonLast!==null )
    return $comments;

  ob_start();
  include Template::file( 'comments/show' );
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
  }

  private static function showDummy( $objectId )
  {
    ob_start();
    include Template::file('comments/show-dummy');
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }

  public static function showSingle( $objectId, $id, $name, $pic, $type, $body, $ctime )
  {
    ob_start();
    include Template::file('comments/show-single');
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }

  public static function save( &$db, &$user )
  {
    $errors = array();
    if ( !$user->id )
      $errors[] = "Je bent niet ingelogd.";

    $qUserId = $db->escape( $user->id );
    $qIp = $db->escape( $_SERVER['REMOTE_ADDR'] );
    $qObjectId = $db->escape( $_POST['objectId'] );
    $qComment = $db->escape( $_POST['comment'] );
    if ( !$qComment )
      $errors[] = "Je kunt geen leeg berichtje opslaan!";

    if ( !sizeof($errors) )
    {
      $q = "insert into comments (ctime, mtime, ip_addr, object_id, user_id, body) values (now(), now(), '$qIp', $qObjectId, $qUserId, '$qComment')";
      $db->query($q);
    }

    return $errors;
  }
}

?>