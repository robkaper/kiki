<?php

/*
 * Static helper class to map controller type names (as used in sections
 * database table) from and to class names and standardised class file
 * paths.
 *
 * Example mappings:
 * type: pages <=> class: Controller_Pages <=> file: controller/pages.php
 * examples/helloworld => Controller_Examples_Helloworld <=> examples/helloword - Controller_Examples_Helloworld - controller/examples/helloworld.php
 *
 * @class ClassHelper
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class ClassHelper
{
	public static function classToFile( $className )
	{
		$partName = str_replace("_", "/", strtolower($className));
		$fileName = $partName. ".php";
		return $fileName;
	}

	public static function fileToClass( $fileName )
	{
		$parts = explode( "/", $fileName );
		foreach ( $parts as &$part )
			$part = ucfirst($part);

		$className = join( "_", $parts );
		return $className;
	}

	public static function typeToClass( $type )
	{
		$parts = explode( "/", $type );
		foreach ( $parts as &$part )
			$part = ucfirst($part);

		$className = "Controller_". join( "_", $parts );
		return $className;
	}
}
