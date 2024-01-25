<?php

/**
 * Class providing various helper functions for checking internal integrity,
 * such as module requirements and database version.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Status
{
  /**
   * Checks required function, include files and extension.
   * @return array the requirements that failed.
   */
  public static function failedRequirements()
  {
    $requirements = array();
    $requirements[] = array( 'name' => 'curl', 'remedy' => 'apt-get install php-curl' );
    $requirements[] = array( 'name' => 'GD', 'function' => 'imagecreatefromjpeg', 'remedy' => 'apt-get install php-gd' );
    $requirements[] = array( 'name' => 'mysqli', 'remedy' => 'apt-get install php-mysql' );
    $requirements[] = array( 'name' => 'PEAR', 'include' => 'PEAR.php', 'remedy' => 'apt-get install php-pear' );

    if ( Config::$i18n )
    {
      $requirements[] = array( 'name' => 'gettext', 'function' => 'bindtextdomain', 'remedy' => "apt-get install php-gettext, or set <strong>Config::\$i18n</strong> to <em>false</em>" );
      $requirements[] = array( 'name' => 'gettext', 'function' => 'textdomain', 'remedy' => "apt-get install php-gettext, or set <strong>Config::\$i18n</strong> to <em>false</em>" );
    }

    $failures = array();

    foreach( $requirements as $requirement )
    {
			if ( isset($requirement['Config']) )
			{
				$property = $requirement['Config'];
				$loaded = ( Config::${$property} !== null );
			}
      else if ( isset($requirement['function']) )
        $loaded = function_exists($requirement['function']);
      else if ( isset($requirement['include']) )
        $loaded = @include_once($requirement['include']);
      else
        $loaded = extension_loaded( $requirement['name'] );

      if ( !$loaded )
        $failures[] = $requirement;
    }
    
    return $failures;
  }

  /**
   * @return string database version or null when no value could be retrieved from the database.
   */
  public static function dbVersion()
  {
    $db = Core::getDb();
    $q = "SELECT `value` FROM `config` WHERE `key`='dbVersion'";
    return $db->getSingleValue($q);
  }

  /**
   * Loads and executes queries from a local file.
   *
   * @fixme Returns immediately in case of an error, without rolling back
   * previous changes: implement transactions.
   * @param Database $db Database object.
   * @param string $fileName Filename of the file with SQL statements.
   *
   * @return boolean True on error, false in case of success.
   */
  public static function sourceSqlFile( &$db, $fileName )
  {
    if ( !file_exists($fileName) )
      return true;

    $output = null;
    $script = file_get_contents($fileName);
    $queries = preg_split( "/;\n/", $script );
    foreach( $queries as $q )
    {
      if ( !trim($q) )
        continue;

      echo "<blockquote>$q;</blockquote>\n";

      $rs = $db->query($q);
      if ( !$rs )
      {
        echo "<p><strong>Error</strong>: <tt>". mysql_error($db->dbh()). "</tt>.</p>";
        return true;
      }
    }
    return false;
  }
}

?>
