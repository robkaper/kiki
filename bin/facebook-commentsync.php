#!/usr/bin/php -q
<?php

  use Kiki\Core;
  use Kiki\Log;
  use Kiki\ObjectCache;

  use Kiki\User;
  use Kiki\Comment;

	use \OAuthException;

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = $db->buildQuery( "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='%s' OR c.service='%s'", 'User_Facebook', 'Kiki\\User\\Facebook' );
  $connectionIds = $db->getObjectIds($q);

  foreach( $connectionIds as $connectionId )
  {
    $objectIds = array();
    $postIds = array();

    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d AND external_id!=0",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $objectIds[$o->external_id] = $o->object_id;
      $postIds[] = "'". $connectionId. "_". $o->external_id. "'";
    }

    $apiUser = User\Factory::getInstance( 'Facebook', $connectionId );

    // Split postIds into smaller sets, FQL has a maximum length and a failure results in OAuthException: An unknown error has occurred
    $postIds = array_chunk( $postIds, 100 );
		foreach( $postIds as $postIdSet )
		{
	    $qPostIds = $db->implode($postIdSet);

	    // Get comments
  	  $q = "select object_id, post_id, fromid, time, text, id, post_fbid, app_id, likes, can_like, user_likes, text_tags, is_private, parent_id from comment where post_id in ($qPostIds) order by time desc";
  	  $rs = $apiUser->api()->api('fql', 'get', array('q' => $q) );
    	if ( !$rs || !isset($rs['data']) )
      	continue;

	    foreach( $rs['data'] as $reply )
  	  {
  	    $externalId = $reply['id'];
  	    list( $postId, $partId ) = explode( "_", $externalId );

  	    $fbUser = User\Factory::getInstance( 'Facebook', $reply['fromid'] );
  	    $localUser = ObjectCache::getByType( 'Kiki\User', $fbUser->kikiUserId() );

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
      	$commentId = $db->getSingleValue( $q );
      	if ( $commentId )
        	continue;
      
	      // Store comment
	      $comment = new Comment();

				// TODO: support nested comments using $reply['parent_id']
				// (Facebook UI does not support that yet, but their post-July 2013 data model does.)
	      $comment->setInReplyToId( $objectId );

	      $comment->setUserId( $localUser->id() );
	      $comment->setConnectionId( $connectionId );
	      $comment->setExternalId( $externalId );
	      $comment->setBody( $reply['text'] );
	      $comment->setCtime( $ctime );
	      $comment->save();

				$object = ObjectCache::get( $objectId );
				$label = method_exists($object, 'title') ? $object->title() : null;

				printf( "%s commented on %s %d (%s):%s%s%s", $fbUser->name(), $object->type(), $objectId, $label, PHP_EOL, $reply['text'], PHP_EOL. PHP_EOL );
			}

	    // Get likes
	    $q = "select post_id, user_id, object_id, object_type from like where post_id in ($qPostIds)";
  	  $rs = $apiUser->api()->api('fql', 'get', array('q' => $q) );
  	  if ( !$rs || !isset($rs['data']) )
    	  continue;

	    foreach( $rs['data'] as $like )
	    {
	      list( $uid, $postId ) = explode("_", $like['post_id']);
	      $objectId = isset($objectIds[$postId]) ? $objectIds[$postId] : 0;

	      $fbUser = User\Factory::getInstance( 'User_Facebook', $like['user_id'] );
	      $localUser = ObjectCache::getByType( 'User', $fbUser->kikiUserId() );

	      if ( !$fbUser->id() )
	      {
	        $fbUser->loadRemoteData( $apiUser->api() );
	        $fbUser->link(0);
	      }

	      $q = "INSERT INTO likes (object_id, user_connection_id,ctime) VALUES($objectId, ". $fbUser->id(). ",now()) on duplicate key UPDATE user_connection_id=". $fbUser->id();
	      $rsLike = $db->query($q);
	      if ( $db->affectedRows($rs) == 1 )
				{
					$object = ObjectCache::get( $objectId );
					$label = method_exists($object, 'title') ? $object->title() : null;
					printf( "%s likes %s %d (%s)". PHP_EOL, $fbUser->name(), $object->type(), $objectId, $label );
				}
			}
    }
  }

