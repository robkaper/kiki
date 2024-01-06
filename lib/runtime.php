<?php

/**
 * Runtime class.
 *
 * Provides methods to store and access runtime variables.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2024 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

// FIXME: move cname here, and add unique key sectionId/cname to db scheme

namespace Kiki;

class Runtime
{
  static public function load( $key, $key2 = null )
  {
    $db = Core::getDb();

    $q = "SELECT `value` FROM `runtime` WHERE `key`='%s' AND `key2` = '%s'";
    $q = $db->buildQuery( $q, $key, $key2 );
    return $db->getSingleValue($q);
  }

  static public function save( $key, $key2, $value )
  {
    $db = Core::getDb();

    $qKey2 = Database::nullable($key2);
    $q = "INSERT INTO `runtime` (`key`, `key2`, `value`) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE `value`='%s'";
    $q = $db->buildQuery( $q, $key, $key2, $value, $value );
    $db->query($q);
  }
}
