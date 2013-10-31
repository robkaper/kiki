<?php

/**
 * Kiki status page. Checks required modules and extensions and also the
 * database version (installing/upgrading when possible and necessary). 
 * Works fine for backwards compatible updates.
 *
 * @warning Breaks when database queries require new columns used to generate
 * this page, mostly notable users (to recognise administration rights or
 * new setup) and config (to check database version).
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  // FIXME: add accountpage/adminpage checks here or to template
  $this->title = _("Kiki Status");

/*
  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }
*/

  $this->template = 'pages/admin';

  $adminsExist = count(Config::$adminUsers);
  $dbVersion = Status::dbVersion();
  Log::debug( "adminUsers: ". print_r( Config::$adminUsers, true) );
  $checkStatus = ( $user->isAdmin() || !$adminsExist );

  if ( !$checkStatus )
  {
    $this->status = 401;
    $this->content = "Access forbidden.";
    return;
  }

  $failedRequirements = Status::failedRequirements();
  if ( count($failedRequirements) )
  {
    ob_start();
    echo "<h2>PHP modules and PEAR/PECL extensions</h2>\n";
    echo "<ul>\n";
    foreach( $failedRequirements as $failedRequirement )
    {
      $remedyStr = isset($failedRequirement['remedy']) ? ( " Potential remedy: <tt>". $failedRequirement['remedy']. "</tt>." ) : null;
      echo "<li><strong>". $failedRequirement['name']. "</strong>: <span style=\"color: red\">disabled</span>.${remedyStr}</li>\n";
    }
    echo "</ul>\n";

    $this->content = ob_get_clean();
    return;
  }

  ob_start();
  echo "<h2>Database</h2>\n";
  
  echo "<ul>\n";
  echo "<li>Data model required: <strong>". Config::dbVersionRequired. "</strong>.</li>\n";

  if ( $dbVersion )
  {
    echo "<li>Data model installed: <strong>$dbVersion</strong>.</li>\n";

    if ( version_compare($dbVersion, Config::dbVersionRequired) < 0 )
    {
      echo "<li>Updating data model:\n";

      // Find update files
      $versions = array();
      foreach ( new DirectoryIterator( Core::getInstallPath(). "/db/" ) as $file )
      {
        if ( !$file->isDot() )
        {
           $version = preg_filter( '/update-(.*)\.sql$/', "$1", $file->getFilename() );
           if ( $version && version_compare($version, $dbVersion) > 0 && version_compare($version, Config::dbVersionRequired) <= 0 )
             $versions[] = $version;
        }
      }

      // Perform in right order 
      natsort( $versions );

      foreach( $versions as $version )
      {
        $file = Core::getInstallPath(). "/db/update-${version}.sql";
        echo "<li>Running update script <tt>$file</tt>:\n";

        $error = Status::sourceSqlFile($db, $file);
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

        $file = Core::getInstallPath(). "/db/core.sql";
        echo "<li>Running install script <tt>$file</tt>:\n";

        $error = Status::sourceSqlFile($db, $file);
        if ( $error )
          echo "<p>Please install manually.</p>\n";

        echo "</li>\n";
      }
      else
        echo "<li>Database connection failed. Please check your configuration (<tt>". Config::configFile(). "</tt>).</li>\n";
    }
    else
      echo "<li>Database not configured. Please create/edit <tt>". Config::configFile(). "</tt>, see <tt>config.php-sample</tt> for an example.</li>\n";
  }

  echo "</ul>\n";

  $this->content = ob_get_clean();
