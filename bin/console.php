#!/usr/bin/php -q
<?php

/**
* @file console.php
* 
* Console front-end for the website.
* 
* Currently hardcoded to print_r the template data for any given (GET) URL
* passing through the router, for debugging purposes.  Should be extended to
* handle specific CLI templates for scripting output and even input
* processing for specific actions to link web-actions and script-actions
* closer together.
* 
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
	$_SERVER['REQUEST_URI'] = isset($argv[2]) ? $argv[2] : die('REQUEST_URI argument missing'. PHP_EOL);

  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

	$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
	$_SERVER['SERVER_PROTOCOL'] = null;

	include_once Kiki::getInstallPath(). "/htdocs/router.php";
