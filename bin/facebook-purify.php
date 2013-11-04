#!/usr/bin/php -q
<?php

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service= 'User_Facebook'";
  $connectionIds = $db->getObjectIds($q);

	$deleteCount = 0;

  foreach( $connectionIds as $connectionId )
  {
    $postIds = array();
		$quotedPostIds = array();

    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $postIds[] = $connectionId. "_". $o->external_id;
      $quotedPostIds[] = "'". $connectionId. "_". $o->external_id. "'";
    }

    $apiUser = Factory_User::getInstance( 'User_Facebook', $connectionId );

    // Split postIds into smaller sets, FQL has a maximum length and a failure results in OAuthException: An unknown error has occurred
		$postIds = array_chunk( $postIds, 100 );
    $quotedPostIds = array_chunk( $quotedPostIds, 100 );

    foreach( $quotedPostIds as $setId => $postIdSet )
		{
	    // Get stream
			$qPostIds = $db->implode($postIdSet);
	    $q = "SELECT post_id, created_time FROM stream WHERE source_id = $connectionId AND post_id IN ($qPostIds)";

	    $rs = $apiUser->api()->api('fql', 'get', array('q' => $q) );
	    if ( !$rs || !isset($rs['data']) )
	      continue;

			$fbPostIds = array();
			$minCreatedTime = null;
			foreach( $rs['data'] as $post )
			{
				$fbPostIds[] = $post['post_id'];
				if ( !isset($minCreatedTime) || $post['created_time'] < $minCreatedTime )
					$minCreatedTime = $post['created_time'];
			}

			if ( !count($fbPostIds) )
				continue;

			$missingIds = array_diff( $postIds[$setId], $fbPostIds );
			if ( !count($missingIds) )
				continue;

			$deleteIds = array();
			foreach( $missingIds as $missingId )
			{
				list( $dummy, $postId ) = explode( "_", $missingId );
				$deleteIds[] = "'". $postId. "'";
			}

			$qDeleteIds = $db->implode($deleteIds);
			// TODO: check if we need to take into account time difference with Facebook servers in time_t/datetime conversion
			$qCtime = date("Y-m-d H:i:s", $minCreatedTime);
			$q = "DELETE p.*, o.* FROM publications p LEFT JOIN objects o ON o.object_id=p.object_id WHERE connection_id=$connectionId AND external_id IN ($qDeleteIds) AND o.ctime>'$minCreatedTime'";
			$rs = $db->query($q);
			$deleteCount += $db->affectedRows($rs);
		}
	}

	if ( $deleteCount )
		echo "Deleted $deleteCount publications referencing deleted Facebook objects". PHP_EOL;	
