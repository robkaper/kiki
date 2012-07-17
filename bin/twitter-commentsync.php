#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='User_Twitter'";
  $connectionIds = $db->getArray($q);

  $objectIds = array();
  $replyObjectIds = array();

  $q = $db->buildQuery( "SELECT comments.external_id, object_id FROM comments LEFT JOIN connections ON connections.id=comments.user_connection_id WHERE connections.service='User_Twitter'" );
  $rs = $db->query($q);
  while( $o = $db->fetchObject($rs) )
    $replyObjectIds[$o->external_id] = $o->object_id;

  $tweets = array(); 

  $storePublicationsAsComment = false;

  foreach( $connectionIds as $connectionId )
  {
    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $objectIds[$o->external_id] = $o->object_id;
    }

    $apiUser = Factory_User::getInstance( 'User_Twitter', $connectionId );
    $rs = $apiUser->api()->get('account/rate_limit_status');
    if ( $rs->remaining_hits < 10 )
    {
      $resetTime = $rs->reset_time_in_seconds;
      $wait = $resetTime - time();
      echo "Less than 10 query hits remaining (". $rs->remaining_hits. "), replenishes in ${wait}s.". PHP_EOL;
      continue;
    }

    $getMore = true;
    $maxId = 0;
    while( $getMore )
    {
      $arrParams = array( 'count' => 800, 'include_rts' => true, 'replies' => 'all' );
      if ( $maxId )
        $arrParams['max_id'] = $maxId;

      $rs = $apiUser->api()->get('statuses/mentions', $arrParams );

      if ( count($rs) == 0 )
        $getMore = false;
      else
      {
        foreach( $rs as $tweet )
        {
          $tweets[$tweet->id] = $tweet;
          $maxId = $tweet->id-1;

          // Tweet already known? No need to get more.
          if ( isset($replyObjectIds[$tweet->id]) )
            $getMore = false;
        }
      }
    }

    $getMore = true;
    $maxId = 0;
    while( $getMore )
    {
      $arrParams = array( 'count' => 800, 'include_rts' => true, 'replies' => 'all' );
      if ( $maxId )
        $arrParams['max_id'] = $maxId;

      $rs = $apiUser->api()->get('statuses/user_timeline', $arrParams );

      if ( count($rs) == 0 )
        $getMore = false;
      else
      {
        foreach( $rs as $tweet )
        {
          $tweets[$tweet->id] = $tweet;
          $maxId = $tweet->id-1;

          // Tweet already known? No need to get more.
          if ( isset($replyObjectIds[$tweet->id]) )
            $getMore = false;
        }
      }
    }
  }

  ksort( $tweets, SORT_NUMERIC );

  foreach( $tweets as $id => $tweet )
  {
    if ( ($storePublicationsAsComment && isset($objectIds[$tweet->id])) || isset($objectIds[$tweet->in_reply_to_status_id]) || isset($replyObjectIds[$tweet->in_reply_to_status_id]) )
    {
      $twUser = Factory_User::getInstance( 'User_Twitter', $tweet->user->id );
      $localUser = ObjectCache::getByType( 'User', $twUser->kikiUserId() );

      if ( !$twUser->id() )
      {
        $twUser->setName( $tweet->user->name );
        $twUser->setScreenName( $tweet->user->screen_name );
        $twUser->setPicture( $tweet->user->profile_image_url );
        $twUser->link( $localUser->id() );
      }
      
      $ctime = strtotime($tweet->created_at);

      // Object ID for publications      
      $objectId = ($storePublicationsAsComment && isset($objectIds[$tweet->id])) ? $objectIds[$tweet->id] : 0;

      // Object ID for replies to publications
      if ( !$objectId )
        $objectId = isset($objectIds[$tweet->in_reply_to_status_id]) ? $objectIds[$tweet->in_reply_to_status_id] : 0;

      // Object ID for replies to replies
      if ( !$objectId )
        $objectId = isset($replyObjectIds[$tweet->in_reply_to_status_id]) ? $replyObjectIds[$tweet->in_reply_to_status_id] : 0;

      $name = $twUser->name();

      $q = $db->buildQuery( "SELECT id FROM connections WHERE external_id=%d", $twUser->externalId() );
      $connectionId = $db->getSingleValue($q);

      // Find comment
      $q = $db->buildQuery( "SELECT id FROM comments WHERE user_connection_id=%d AND external_id=%d", $connectionId, $tweet->id );
      $commentId = $db->getSingleValue( $q );
      if ( $commentId )
        continue;

      // Store comment
      $comment = new Comment();
      $comment->setInReplyToId( $objectId );
      $comment->setUserId( $localUser->id() );
      $comment->setConnectionId( $connectionId );
      $comment->setExternalId( $tweet->id );
      $comment->setBody( $tweet->text );

      $comment->setCtime( $ctime );
      $comment->save();

      $replyObjectIds[$tweet->id] = $objectId ? $objectId : $comment->objectId();
      print_r($comment);
    }
  }
