<?

/**
 * Class providing various helper functions for checking internal integrity,
 * such as module requirements and database version.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Status
{
  public static function failedRequirements()
  {
    $requirements = array();
    $requirements[] = array( 'name' => 'curl', 'remedy' => 'apt-get install php5-curl' );
    $requirements[] = array( 'name' => 'mysql', 'remedy' => 'apt-get install php5-mysql' );
    $requirements[] = array( 'name' => 'PEAR', 'include' => 'PEAR.php', 'remedy' => 'apt-get install php-pear' );
    $requirements[] = array( 'name' => 'Mail_RFC822 (PEAR)', 'include' => 'Mail/RFC822.php', 'remedy' => 'pear install -a Mail' );
    $requirements[] = array( 'name' => 'Net_SMTP (PEAR)', 'include' => 'Net/SMTP.php', 'remedy' => 'pear install -a Net_SMTP' );
    $requirements[] = array( 'name' => 'Fileinfo (PECL', 'function' => 'finfo_open', 'remedy' => 'pecl install Fileinfo' );
    $requirements[] = array( 'name' => 'Mailparse (PECL)', 'function' => 'mailparse_msg_create', 'remedy' => 'pecl install Mailparse' );

    $failures = array();

    foreach( $requirements as $requirement )
    {
      if ( isset($requirement['function']) )
        $loaded = function_exists($requirement['function']);
      else if ( isset($requirement['include']) )
        $loaded = @include_once($requirement['include']);
      else
        $loaded = extension_loaded( $requirement['name'] );

      if ( !$loaded )
        $failures[] = $requirement;
    }
    
    return count($failures) ? $failures : false;
  }

  public static function dbVersion()
  {
    $db = $GLOBALS['db'];
    $q = "select value from config where `key`='dbVersion'";
    return $db->getSingleValue($q);
  }
}

?>
