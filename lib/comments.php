<?

/**
 * Class for comments attached to objects.
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
    $q = "select c.id, c.body, o.ctime, c.user_id, con.service, con.external_id FROM comments c LEFT JOIN objects o ON o.object_id=c.object_id LEFT JOIN users u ON c.user_id=u.id LEFT JOIN connections con ON c.user_connection_id=con.id WHERE c.in_reply_to_id=$qObjectId $qLast order by o.ctime asc";
    $rs = $db->query($q);
    if ( $rs && $db->numrows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $commentAuthor = ObjectCache::getByType( 'User', $o->user_id );
        if ( $commentAuthor )
        {
          if ( $o->external_id )
          {
            if ( !$commentAuthor->id() )
            {
              $commentAuthor->getStoredConnections();
            }
            $connection = $commentAuthor->getConnection($o->service. "_". $o->external_id);
            if ( $connection )
            {
              $serviceName = $connection->serviceName();
              $name = $connection->name();
              $pic = $connection->picture();
            }
            else
            {
              $serviceName = 'None'; // SNH
              $name = $commentAuthor->name();
              $pic = $commentAuthor->picture();
            }
          }
          else
          {
            $serviceName = 'None'; // Kiki
            $name = $commentAuthor->name();
            $pic = $commentAuthor->picture();
          }
        }
        else
        {
          $serviceName = 'None';
          $name = "Anonymous";
          $pic = null;
        }

        if ( $jsonLast !== null )
        {
          $comments[] = Comments::showSingle( $objectId, $o->id, $name, $pic, $serviceName, $o->body, $o->ctime );
        }
        else
        {
          $comment = array(
            'objectId' => $objectId,
            'id' => $o->id,
            'name' => $name,
            'pic' => $pic,
            'type' => $serviceName,
            'body' => $o->body,
            'ctime' => $o->ctime,
            'dateTime' => date("c", strtotime($o->ctime)),
            'relTime' => Misc::relativeTime($o->ctime)
          );
          $comments[] = $comment;
        }
      }
    }
    else if ( $jsonLast===null )
      $comments[] = Comments::showDummy( $objectId );

    if ( $jsonLast!==null )
    {
      return $comments;
    }

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
}

?>