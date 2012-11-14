#!/usr/bin/php -q
<?php

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='User_Twitter'";
  $connectionIds = $db->getArray($q);

	$deleteCount = 0;

  foreach( $connectionIds as $connectionId )
  {
		$lastTweet = null;
		$tweetIds = array();

    $q = $db->buildQuery(
      "SELECT external_id FROM publications WHERE connection_id=%d",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
			$tweetIds[] = $o->external_id;

    $apiUser = Factory_User::getInstance( 'User_Twitter', $connectionId );
    $rs = $apiUser->api()->get('account/rate_limit_status');
    if ( !$rs )
    {
      echo "No valid result from Twitter (connection $connectionId).";
      continue;
    }

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

      $rs = $apiUser->api()->get('statuses/user_timeline', $arrParams );

      if ( count($rs) == 0 )
        $getMore = false;
      else
      {
        foreach( $rs as $lastTweet )
        {
          $twTweetIds[] = $lastTweet->id_str;
          $maxId = $lastTweet->id-1;
        }
      }
    }

		if ( !isset($lastTweet) )
			continue;

		$deleteIds = array_diff( $tweetIds, $twTweetIds );

		if ( !count($deleteIds) )
			continue;	

		$qDeleteIds = $db->implode($deleteIds);
		$qCtime = date( "Y-m-d H:i:s", strtotime($lastTweet->created_at) );
    $q = "DELETE p.*, o.* FROM publications p LEFT JOIN objects o ON o.object_id=p.object_id WHERE connection_id=$connectionId AND external_id IN ($qDeleteIds) AND ctime>'$qCtime'";
    $rs = $db->query($q);
		$deleteCount += $db->affectedRows($rs);
  }

	if ( $deleteCount )
		echo "Deleted $deleteCount publications referencing deleted Twitter objects". PHP_EOL;
