<?
  include_once "../lib/init.php";

  $page = new Page( "Kiki CMS Status" );
  $page->header();

  function sourceSqlFile( &$db, $fileName )
  {
    if ( !file_exists($fileName) )
      return true;

    $script = file_get_contents($fileName);
    $queries = preg_split( "/;\n/", $script );
    foreach( $queries as $q )
    {
      if ( !$q )
        continue;

      echo "<blockquote>$q</blockquote>\n";

      $rs = $db->query($q);
      if ( !$rs )
      {
        echo "<p><strong>Error</strong>: <tt>". mysql_error($db->dbh()). "</tt>.</p>";
        return true;
      }
    }
    return false;
  }

  echo "<h2>PHP modules and PEAR/PECL extensions</h2>\n";

  $extensions = array( 'curl' => null, 'mysql' => null , 'Mailparse (PECL)' => 'mailparse_msg_create' );

  echo "<ul>\n";
  foreach( $extensions as $extension => $function )
  {
    if ( $function )
      $loaded = function_exists($function);
    else
      $loaded = extension_loaded( $extension );
    $loadedStr = $loaded ? "enabled" : "<span style=\"color: red\">disabled</span>";
    echo "<li><strong>$extension</strong>: ${loadedStr}.</li>\n";
  }
  echo "</ul>\n";

  echo "<h2>Database</h2>\n";
  
  $q = "select value from config where `key`='dbVersion'";
  $dbVersion = $db->getSingleValue($q);

  echo "<ul>\n";
  echo "<li>Data model required: <strong>". Config::$dbVersionRequired. "</strong>.</li>\n";

  if ( $dbVersion )
  {
    echo "<li>Data model installed: <strong>$dbVersion</strong>.</li>\n";

    if ( version_compare($dbVersion, Config::$dbVersionRequired) < 0 )
    {
      echo "<li>Updating data model:\n";

      // Find update files
      $versions = array();
      foreach ( new DirectoryIterator( $GLOBALS['kiki']. "/db/" ) as $file )
      {
        if ( !$file->isDot() )
        {
           $version = preg_filter( '/update-(.*)\.sql/', "$1", $file->getFilename() );
           if ( $version && version_compare($version, $dbVersion) >= 0 && version_compare($version, Config::$dbVersionRequired) <= 0 )
             $versions[] = $version;
        }
      }

      // Perform in right order 
      natsort( $versions );

      foreach( $versions as $version )
      {
        $file = $GLOBALS['kiki']. "/db/update-${version}.sql";
        echo "<li>Running update script <tt>$file</tt>:\n";

        $error = sourceSqlFile($db, $file);
        if ( $error )
        {
          echo "<p>Please upgrade manually.</p>\n";
          echo "</li>\n";
          break;
        }
        else
        {
          $db->query( "update config set value='$version' where `key`='dbVersion'" );
          echo "</li>\n";
        }
      }
      echo "</li>\n";
    }
  }
  else
  {
    if ( Config::$dbUser )
    {
      if ( $db->connected() )
      {
        echo "<li>Database tables not installed.</li>\n";

        $file = $GLOBALS['kiki']. "/db/core.sql";
        echo "<li>Running install script <tt>$file</tt>:\n";

        $error = sourceSqlFile($db, $file);
        if ( $error )
          echo "<p>Please install manually.</p>\n";
          
        echo "</li>\n";
      }
      else
        echo "<li>Database connection failed. Please check your configuration (<tt>". Config::configFile(). "</tt>).</li>\n";
    }
    else
      echo "<li>Database not configured. Please create/edit <tt>". Config::configFile(). "</tt>, see <tt>examples/config.php</tt> for an example.</li>\n";
  }

  echo "</ul>\n";
    
  $page->footer();
?>
