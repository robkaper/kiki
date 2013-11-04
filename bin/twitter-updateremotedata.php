#!/usr/bin/php -q
<?php

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

	// TODO: make admin check, assume all admins have working connections capable of acting as apiUser in TwitterConnectionService->getApiUsers()
  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='User_Twitter' LIMIT 1";
  $apiConnectionId = $db->getSingleValue($q);
	$apiUser = Factory_User::getInstance( 'User_Twitter', $apiConnectionId );

  $q = "SELECT DISTINCT external_id AS id FROM connections WHERE service='User_Twitter'";
  $connectionIds = $db->getObjectIds($q);

	$chunks = array_chunk($connectionIds, 100);
	foreach( $chunks as $chunk )
	{
		$rs = $apiUser->api()->get( "users/lookup", array( "user_id" => implode(",", $chunk) ) );
		foreach( $rs as $rsUser )
		{
			$twUser = Factory_User::getInstance( 'User_Twitter', $rsUser->id );
			$twUser->setName( $rsUser->name );
			$twUser->setScreenName( $rsUser->screen_name );
			$twUser->setPicture( $rsUser->profile_image_url );
			$twUser->link();
		}
	}
