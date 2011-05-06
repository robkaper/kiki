<?

class Comments
{
  public static function form( &$user, $objectId )
  {
    global $anyUser;
    
    $content = "<div id=\"commentForm_$objectId\" class=\"jsonupdate shrunk\">\n";
    $content .= $anyUser ? Boilerplate::commentForm($user, $objectId) : Boilerplate::commentLogin();
    $content .= "</div>\n";
    return $content;
  }

  public static function show( &$db, &$user, $objectId, $jsonLast=null )
  {
    $comments = array();
    $content = "";

    if ( $jsonLast===null )
      $content .= "<div id=\"comments_$objectId\" class=\"comments\">\n";

    $qObjectId = $db->escape( $objectId );
    $qLast = $jsonLast ? ("and c.id>". $db->escape($jsonLast)) : "";
    $q = "select c.id,c.body,c.ctime, u.facebook_user_id, u.twitter_user_id from comments c, users u where c.object_id=$qObjectId and c.user_id=u.id $qLast order by ctime asc";
    $rs = $db->query($q);
    if ( $rs && $db->numrows($rs) )
    {
      while( $o = $db->fetch_object($rs) )
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

      if ( $jsonLast!==null )
        $comments[] = Comments::showComment( $objectId, $o->id, $name, $pic, $type, $o->body, $o->ctime );
      else
        $content .= Comments::showComment( $objectId, $o->id, $name, $pic, $type, $o->body, $o->ctime );
    }
  }
  else if ( $jsonLast===null )
    $content .= "<div id=\"comment_${objectId}_0\" class=\"comment dummy\" style=\"min-height: 0px;\"><p>\nPlaats de eerste reactie!</p>\n</div>\n";

  if ( $jsonLast!==null )
    return $comments;

  $content .= Comments::form( $user, $objectId );
  $content .= "</div>\n";
  return $content;
  }

  public static function showComment( $objectId, $id, $name, $pic, $type, $body, $ctime )
  {
    $content = "";
    $content .= "<div class=\"comment\" id=\"comment_${objectId}_${id}\">\n";
    $content .= Boilerplate::socialImage( $type, $name, $pic );
    $content .= "<div class=\"commentTxt\">\n";
    $content .= "<a href=\"#\">$name</a> ". htmlspecialchars($body). "\n";
    $content .= "<br /><time class=\"relTime\">". Misc::relativeTime($ctime). " geleden</time>\n";
    $content .= "</div>\n";
    $content .= "</div>\n";
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