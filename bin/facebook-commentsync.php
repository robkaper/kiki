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
    foreach( $rs['data'] as $reply )
    {
      list( $uid, $postId, $partId ) = explode("_", $reply['id']);
      $externalId = $postId. "_". $partId;

      $fbUser = Factory_User::getInstance( 'User_Facebook', $reply['fromid'] );
      $localUser = ObjectCache::getByType( 'User', $fbUser->kikiUserId() );

      if ( !$fbUser->id() )
      {
        $fbUser->loadRemoteData( $apiUser->api() );
        $fbUser->link(0);
      }

      $ctime = $reply['time'];
      $objectId = isset($objectIds[$postId]) ? $objectIds[$postId] : 0;

      $q = $db->buildQuery( "SELECT id FROM connections WHERE external_id=%d", $fbUser->externalId() );
      $connectionId = $db->getSingleValue($q);

      // Find comment
      $q = $db->buildQuery( "SELECT id FROM comments WHERE user_connection_id=%d AND external_id='%s'", $connectionId, $externalId );
      echo $q. PHP_EOL;
      $commentId = $db->getSingleValue( $q );
      if ( $commentId )
        continue;
      
      // Store comment
      $comment = new Comment();
      $comment->setInReplyToId( $objectId );
      $comment->setUserId( $localUser->id() );
      $comment->setConnectionId( $connectionId );
      $comment->setExternalId( $externalId );
      $comment->setBody( $reply['text'] );
      $comment->setCtime( $ctime );
      $comment->save();
            
      print_r($comment);
    }
}

