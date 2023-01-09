<?php

/*
 * Static helper class to map controller type names (as used in sections
 * database table) from and to class names and standardised class file
 * paths.
 *
 * Example mappings:
 * type: pages <=> class: Controller\Pages <=> file: controller/pages.php
 * type: examples/helloworld <=> class: Controller\Examples\Helloworld <=> file: controller/examples/helloworld.php
 *
 * @class ClassHelper
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class ClassHelper
{
	public static function isInKikiNamespace( $className )
	{
		return preg_match( '#^Kiki\\\#', $className );
	}

	public static function isInCustomNamespace( $className )
	{
		return preg_match( '#^'. \Kiki\Config::$namespace. '\\\#', $className );
	}

	public static function classToType( $className )
	{
		return str_replace("\\", "/", strtolower($className));
	}


	public static function classToFile( $className )
	{
		// echo "<br>ch: $className";
		$partName = self::classToType($className);
		$fileName = str_replace("\\", "/", $partName). ".php";
		// echo "<br>fn: $className";
		$fileName = preg_replace("#^(kiki|". strtolower(\Kiki\Config::$namespace). ")/#", "", $fileName );
		// echo "<br>fn: $className";
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

	// TODO: isn't this basically ::factory()
	public static function bareToNamespace( $className )
	{
		// echo "<br>z1". $className;

		if ( class_exists( $className ) )
			return $className;
					
		$tryClassName = '\\'. Config::$namespace. '\\'. $className;
		// echo "<br>zl". $tryClassName;
		if ( class_exists( $tryClassName ) )
			return $tryClassName;

		$tryClassName = '\\Kiki\\'. $className;
		// echo "<br>zk". $tryClassName;
		if ( class_exists( $tryClassName ) )
			return $tryClassName;

		// echo "<br>zd". $className;
		
		return $className;
	}

	public static function typeToClass( $type )
	{
		// echo "typeToClass for $type". PHP_EOL;
		$parts = explode( "/", $type );
		foreach ( $parts as &$part )
			$part = ucfirst($part);

		$className = "Controller\\". join( "\\", $parts );
		// echo "returns className $className". PHP_EOL;
		return $className;
	}
}
