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
  public static function show( &$db, &$user, $objectId, $jsonLast=null )
  {
    $comments = array();

    $qObjectId = $db->escape( $objectId );
    $qLast = $jsonLast ? ("and c.id>". $db->escape($jsonLast)) : "";
    $q = "select c.id, c.body, c.ctime, c.user_id, u.facebook_user_id, u.twitter_user_id from comments c, users u where c.object_id=$qObjectId and c.user_id=u.id $qLast order by ctime asc";
    $rs = $db->query($q);
    if ( $rs && $db->numrows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $commentAuthor = ObjectCache::getByType( 'User', $o->user_id );
        if ( $commentAuthor )
        {
          $serviceName = $commentAuthor->serviceName();
          $name = $commentAuthor->name();
          $pic = $commentAuthor->picture();
        }
        else
        {
          $serviceName = "unknown";
          $name = "name";
          $pic = null;
        }

        $comment = array(
          'objectId' => $objectId,
          'id' => $o->id,
          'name' => $name,
          'pic' => $pic,
          'type' => $serviceName,
          'body' => $o->body,
          'ctime' => $o->ctime,
          'relTime' => Misc::relativeTime($o->ctime)
        );
        $comments[] = $comment;
      }
    }
    else if ( $jsonLast===null )
      $comments[] = Comments::showDummy( $objectId );

    if ( $jsonLast!==null )
      return $comments;

    $template = new Template( 'parts/comments' );
    $template->assign( 'objectId', $objectId );
    $template->assign( 'comments', $comments );
    return $template->fetch();
  }

  private static function showDummy( $objectId )
  {
    $template = new Template( 'parts/comments-dummy' );
    $template->assign( 'objectId', $objectId );
    return $template->fetch();
  }

  public static function showSingle( $objectId, $id, $name, $pic, $type, $body, $ctime )
  {
    $comment = array(
      'objectId' => $objectId,
      'id' => $id,
      'name' => $name,
      'pic' => $pic,
      'type' => $type,
      'body' => $body,
      'ctime' => $ctime,
      'relTime' => Misc::relativeTime($ctime)
    );
      
    $template = new Template( 'parts/comments-single' );
    $template->assign( 'comment', $comment );
    $template->assign( 'objectId', $objectId );
    return $template->fetch();
  }

  public static function save( &$db, &$user, $objectId )
  {
    $errors = array();

    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";
    if ( !$comment = $_POST['comment'] )
      $errors[] = "Je kunt geen leeg berichtje opslaan!";

    if ( !sizeof($errors) )
    {
      $q = $db->buildQuery(
        "INSERT INTO comments (ctime, mtime, ip_addr, object_id, user_id, body) values (now(), now(), '%s', %d, %d, '%s')",
        $_SERVER['REMOTE_ADDR'], $objectId, $user->id(), $comment
      );

      $db->query($q);
    }

    return $errors;
  }
}

?>