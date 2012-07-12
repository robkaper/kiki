#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service= 'User_Facebook'";
  $connectionIds = $db->getArray($q);

  foreach( $connectionIds as $connectionId )
  {
    $objectIds = array();
    $postIds = array();

    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $objectIds[$o->external_id] = $o->object_id;
      $postIds[] = "'". $connectionId. "_". $o->external_id. "'";
    }

    $apiUser = Factory_User::getInstance( 'User_Facebook', $connectionId );

    $qPostIds = $db->implode($postIds);
    $q = "select xid, object_id, post_id, fromid, time, text, id, username, reply_xid, post_fbid, app_id, likes, comments, can_like, user_likes, text_tags, is_private from comment where post_id in ($qPostIds) order by time desc LIMIT 5000";
    $rs = $apiUser->api()->api('fql', 'get', array('q' => $q) );
    $i=0;
    foreach( $rs['data'] as $comment )
    {
      list( $uid, $postId ) = explode("_", $comment['post_id']);

      $fbUser = Factory_User::getInstance( 'User_Facebook', $comment['fromid'] );
      $kikiUserIds = $fbUser->kikiUserIds();
      if ( count($kikiUserIds) )
      {
        $localUser = new User($fbUser->kikiUserId());
        // echo "found for ". $comment['fromid']. ": ". $localUser->id(). "/". $localUser->name(). PHP_EOL;
      }
      else if ( $fbUser->id() )
      {
        // echo "remote found for ".  $comment['fromid']. ": ". $fbUser->externalId(). "/". $fbUser->name(). PHP_EOL;
        $localUser = new User();
      }
      else
      {
        $localUser = new User();

        $fbUser->loadRemoteData($apiUser->api());
        $fbUser->link(0);
    
        // echo "created new user ". $fbUser->externalId(). "/". $fbUser->name(). PHP_EOL;
      }

      $ctime = $comment['time'];
      $objectId = isset($objectIds[$postId]) ? $objectIds[$postId] : 0;
      $text = $comment['text'];
      $name = $fbUser->name();

      $q = $db->buildQuery( "SELECT id FROM connections WHERE external_id=%d", $fbUser->externalId() );
      $connectionId = $db->getSingleValue($q);

      // Find comment
      // FIXME: properly store externalId instead of checking timestamp
      $q = $db->buildQuery( "SELECT c.id from comments c LEFT JOIN objects o ON o.object_id=c.object_id WHERE ctime=from_unixtime(%d) and in_reply_to_id=%d and (user_id=%d or user_connection_id=%d)", $ctime, $objectId, $localUser->id(), $connectionId );
      $commentId = $db->getSingleValue( $q );
      if ( $commentId )
        continue;
      
      // Store comment
      $comment = new Comment();
      $comment->setInReplyToId( $objectId );
      $comment->setUserId( $localUser->id() );
      $comment->setConnectionId( $connectionId );
      $comment->setBody( $mention->text );
      $comment->setCtime( $ctime );
      $comment->save();
            
      print_r($comment);
    }
}

