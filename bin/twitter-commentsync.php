#!/usr/bin/php -q
<?php

	use Kiki\Core;
	use Kiki\Log;
	use Kiki\ObjectCache;

	use Kiki\User;
	use Kiki\Comment;

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = $db->buildQuery( "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='%s' OR c.service='%s' OR c.service='%s'", 'User_Twitter', 'Twitter', 'Kiki\User\Twitter' );
  $connectionIds = $db->getObjectIds($q);

  $objectIds = array();
  $replyObjectIds = array();

  $q = $db->buildQuery( "SELECT comments.external_id, object_id, in_reply_to_id FROM comments LEFT JOIN connections ON connections.id=comments.user_connection_id WHERE connections.service='%s' OR connections.service='%s' OR connections.service='%s'", 'User_Twitter', 'Twitter', 'Kiki\User\Twitter' );
  $rs = $db->query($q);
	if ( $db->numRows($rs) )
	{
		while( $o = $db->fetchObject($rs) )
		{
			// TODO: also store object_id, not doing so flattens all comments and all nesting/threading information is lost
			$replyObjectIds[$o->external_id] = $o->in_reply_to_id;
		}
	}

  $tweets = array(); 

  $storePublicationsAsComment = false;

  foreach( $connectionIds as $connectionId )
  {
    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d AND external_id!=0",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $objectIds[$o->external_id] = $o->object_id;
    }

    $apiUser = User\Factory::getInstance( 'Twitter', $connectionId );

		try {
	    $rs = $apiUser->api()->get('application/rate_limit_status');
		}
		catch (User\Exception $e) {
			echo "API call failed for user ". print_r($apiUser, true );
			Log::error( "API call failed for user ". print_r($apiUser, true) );
			continue;
		}

    if ( !isset($rs) )
    {
      echo "No valid result from Twitter (connection $connectionId).". PHP_EOL;
      continue;
    }

		$remainingHits = $rs->resources->application->{'/application/rate_limit_status'}->remaining;
    if ( $remainingHits < 5 )
    {
      $resetTime = $rs->resources->application->{'/application/rate_limit_status'}->reset;
      $wait = $resetTime - time();
      echo "Less than 10 query hits remaining ($remainingHits), replenishes in ${wait}s.". PHP_EOL;
      continue;
    }

    $getMore = true;
    $maxId = 0;
    while( $getMore )
    {
      $arrParams = array( 'count' => 800, 'include_rts' => 1, 'replies' => 'all' );
      if ( $maxId )
        $arrParams['max_id'] = $maxId;

      $rs = $apiUser->api()->get('statuses/mentions_timeline', $arrParams );

      if ( count($rs) == 0 )
        $getMore = false;
      else
      {
        foreach( $rs as $tweet )
        {
          if ( !isset($tweet->id) )
          {
						echo "Non object result: ". print_r($tweet). PHP_EOL;
            continue;
          }

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
					if ( !isset($tweet->id) )
					{
						echo "Non object result: ". print_r($tweet). PHP_EOL;
						continue;
					}

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
      $twUser = User\Factory::getInstance( 'Twitter', $tweet->user->id );
      $localUser = ObjectCache::getByType( 'Kiki\User', $twUser->kikiUserId() );

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

      $q = $db->buildQuery( "SELECT id FROM connections WHERE external_id=%d", $twUser->externalId() );
      $tweetConnectionId = $db->getSingleValue($q);

      // Find comment
      $q = $db->buildQuery( "SELECT id FROM comments WHERE user_connection_id=%d AND external_id='%s'", $tweetConnectionId, $tweet->id );
      $commentId = $db->getSingleValue( $q );
      if ( $commentId )
        continue;

      // Store comment
      $comment = new Comment();
      $comment->setInReplyToId( $objectId );
      $comment->setUserId( $localUser->id() );
      $comment->setConnectionId( $tweetConnectionId );
      $comment->setExternalId( $tweet->id );
      $comment->setBody( $tweet->text );

      $comment->setCtime( $ctime );
      $comment->save();

      // TODO: also store object_id, not doing so flattens all comments and all nesting/threading information is lost
      $replyObjectIds[$tweet->id] = $objectId ? $objectId : $comment->objectId();

      printf( "%s commented on %d:%s%s%s", $twUser->name(), $objectId, PHP_EOL, $tweet->text, PHP_EOL. PHP_EOL );
    }
    else if ( isset($tweet->retweeted_status) && $tweet->user->id != $connectionId )
    {
      // TODO: store quoted retweet
      // echo "==== retweet ====". print_r($tweet,true). "==== end retweet ====". PHP_EOL;
    }
  }
