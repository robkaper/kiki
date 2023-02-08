#!/usr/bin/php -q
<?php

	use Kiki\User;

  use \OAuthException;

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

	// TODO: make admin check, assume all admins have working connections capable of acting as apiUser in TwitterConnectionService->getApiUsers()
  $q = $db->buildQuery( "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN user_connections uc ON uc.external_id=p.connection_id WHERE uc.service='%s' OR uc.service='%s' OR uc.service='%s' LIMIT 1", 'User_Twitter', 'Twitter', 'Kiki\User\Twitter' );
  $apiConnectionId = $db->getSingleValue($q);
	$apiUser = User\Factory::getInstance( 'Twitter', $apiConnectionId );

  $q = $db->buildQuery( "SELECT DISTINCT external_id AS id FROM user_connections WHERE service='%s' OR service='%s' OR service='%s'", 'User_Twitter', 'Twitter', 'Kiki\User\Twitter' );
  $connectionIds = $db->getObjectIds($q);

	$chunks = array_chunk($connectionIds, 100);
	foreach( $chunks as $chunk )
	{
		$rs = $apiUser->api()->get( "users/lookup", array( "user_id" => implode(",", $chunk) ) );
		foreach( $rs as $rsUser )
		{
			// echo "$rsUser->id / $rsUser->name / $rsUser->screen_name / $rsUser->profile_image_url_https". PHP_EOL;
			$twUser = User\Factory::getInstance( 'Twitter', $rsUser->id );
			$twUser->setName( $rsUser->name );
			$twUser->setScreenName( $rsUser->screen_name );
			$twUser->setPicture( $rsUser->profile_image_url_https );
			$twUser->link();
		}
	}
