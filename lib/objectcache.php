<?

/**
 * Provides a rudimentary Object cache.
 *
 * Simple reference store 
 * for data objects of whom a single instance is (likely to be) repeatedly
 * used in the same script in different scopes, such as users when
 * displaying the authers of articles or comment threads.
 *
 * Storing and retrieving the instance from this cache might increase memory
 * use (assuming PHP properly frees garbage when leaving a scope), but
 * prevents unnecessary class construction plus associated initialisation
 * and database loading. For most sites, this tradeoff seems to work.
 *
 * @warning Stores references: retrieved and stored objects should not be
 * reinstanced by changed their ID manually or using setFromObject() unless
 * explicitely cloned first.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class ObjectCache
{
  /**
   * @var Reference store using objectId as key.
   */
  static private $objects = array();

  /**
   * @var Reference store using object type and regular Id as key.
   */
  static private $objectsByType = array();

  /**
   * Clears the cache.
   */
  static public function reset()
  {
    self::$objects = array();
    self::$objectsByType = array();
  }

  /**
   * Stores an object in the cache.
   */
  static public function store( &$object )
  {
    // Do not store objects with Id zero, those should always be created new
    // and clean.
    if ( !$object->objectId() || !$object->id() )
      return;

    // Store by objectId
    self::$objects[$object->objectId()] = $object;

    // Store by object type and Id
    if ( !isset(self::$objectsByType[$object->type()]) )
      self::$objectsByType[$object->type()] = array();
    self::$objectsByType[$object->type()][$object->id()] = $object;
  }

  /**
   * Retrieves an object from the cache.
   *
   * @param int $objectId
   * @param boolean $create When true, attempt to create objects not found
   * in the cache.
   * @return mixed The object, or null when not found nor created.
   */
  static public function get( $objectId, $create=true )
  {
    if ( isset(self::$objects[$objectId]) )
      return self::$objects[$objectId];

    return $create ? self::create( null, 0, $objectId ) : null;
  }

  /**
   * Retrieves an object from the cache.
   *
   * @param string $type Type (class name) of the object
   * @param int $id
   * @param boolean $create When true, attempt to create objects not found
   * in the cache.
   * @return mixed The object, or null when not found nor created.
   */
  static public function getByType( $type, $id=0, $create=true )
  {
    if ( isset(self::$objectsByType[$type]) && isset(self::$objectsByType[$type][$id]) )
      return self::$objectsByType[$type][$id];

    return $create ? self::create( $type, $id ) : null;
  }

  /**
   * Retrieves the type of an object from the database.
   *
   * @param int $objectId
   * @return string Type (class name) of the object
   */
  static private function getType( $objectId )
  {
    $db = $GLOBALS['db'];
    $q = $db->buildQuery( "select type from objects where object_id=%d", $objectId );
    return $db->getSingleValue($q);
  }

  /**
   * Creates an object (and stores it in the cache).
   *
   * Either specify an objectId (which loads type from database itself) or both type and Id.
   *
   * @return string Type (class name) of the object
   * @param int $id
   * @param int $objectId
   * @return object The object, or null when type undetermined or not a valid class.
   */
  static private function create( $type = null, $id = 0, $objectId = 0 )
  {
    if ( !$type && $objectId )
      $type = self::getType( $objectId );
    if ( !$type || !class_exists($type) )
      return null;

    $object = new $type($id, $objectId);
    self::store($object);
    return $object;
  }
}

?>