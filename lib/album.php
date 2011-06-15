<?

/**
* @file lib/album.php
* Provides the Album class.
* @class Album
* Provides a picture album.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
* @todo Support more media types (template assumes resources are can be used
*   in an img-element).
*/

class Album
{
  private $db;

  public $id, $title;

  /**
  * Initialises an album.
  * @param $id [int] (optional) ID of the album, for quick loading
  */
  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    
    $this->reset();
    
    if ( $id )
      $this->load( $id );
  }

  /**
  * Resets member variables to their pristine state.
  */
  public function reset()
  {
    $this->id = 0;
    $this->title = null;
  }

  /**
  * Loads an album from the database.
  * @param $id [int] ID of the album
  */
  public function load( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select id,title from albums where id=$qId";
    $o = $this->db->getSingle($q);
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->title = $o->title;
  }

  /**
  * Provides the full URL of the album to be used in external references.
  * @param $pictureId [int] (optional) ID of the picture to start with, if none is given the most recent picture will be used
  * @return string full URL of the album
  */
  public function url( $pictureId=0 )
  {
    return "http://". $_SERVER['SERVER_NAME']. $this->uri($pictureId);
  }

  /**
  * Provides the local URI part of the album.
  * @param $pictureId [int] (optional) ID of the picture to start with, if none is given the most recent picture will be used
  * @return string local URI part of the album
  */
  public function uri( $pictureId=0 )
  {
    return Config::$kikiPrefix. "/album/$this->id/". ($pictureId ? "$pictureId/" : null);
  }

  /**
  * Generates the HTML for the album viewer.
  * @param $pictureId [int] (optional) ID of the picture to start with, if none is given the most recent picture will be used
  */
  public function show( $pictureId=0 )
  {
    $photos = array();

    // Find latest if not starting picture specified
    if ( !$pictureId )
    {
      $q = "select p.id from pictures p, album_pictures ap where p.id=ap.picture_id and ap.album_id=$this->id order by p.storage_id desc limit 1";
      $pictureId = $this->db->getSingleValue($q);
    }

    if ( !$pictureId )
    {
      include Template::file( 'album/show-empty' );
      return;
    }

    // Picture details
    $qPictureId = $this->db->escape($pictureId);
    $q = "select title, storage_id from pictures where id=$qPictureId";
    $o = $this->db->getSingle($q);
    $pictureTitle = $o->title;
    $storageId = $o->storage_id;

    $imgId = $pictureId;
    $imgUrl = Storage::url($storageId);
    $leftClass = self::findPrevious( $this->id, $imgId ) ? null : " disabled";
    $rightClass = self::findNext( $this->id, $imgId ) ? null : " disabled";

    include Template::file( 'album/show' );
  }

  /**
  * Adds pictures to the album. All pictures get the same title/description, if this is not desired this method must be called separately for each picture.
  * @param $title [string] title of the picture(s)
  * @param $description [string] decription of the picture(s)
  * @param $storageIds [array] list of Storage IDs of the picture(s)
  * @return array the pictures (themselves an array: id, title, decription) inserted
  * @todo don't use storage IDs but create an actual Picture class, this
  *   method should only link the pictures into the album.
  */
  public function addPictures( $title, $description, $storageIds )
  {
    // Stores pictures into the database
    $pictures = array();
    foreach( $storageIds as $storageId )
    {
      $qTitle = $this->db->escape( $title );
      $qDesc = $this->db->escape( $description );
      $q = "insert into pictures (title, description, storage_id) values ('$qTitle', '$qDesc', $storageId)";
      $rs = $this->db->query($q);
      $pictures[]= array( 'id' => $this->db->lastInsertId($rs), 'title' => $title, 'description' => $description );
    }

    // Link pictures into album
    foreach( $pictures as $picture )
    {
      $pictureId = $picture['id'];
      $q = "insert into album_pictures (album_id,picture_id) values ($this->id, $pictureId)";
      $this->db->query($q);
    }

    return $pictures;
  }

  /**
  * Finds the previous picture in an album.
  * @param $albumId [int] ID of the album
  * @param $pictureId [int] ID of the current picture
  * @return ID of the previous picture (null if none)
  */
  public static function findPrevious( $albumId, $pictureId )
  {
    $db = $GLOBALS['db'];
    $qAlbumId = $db->escape( $albumId );
    $qPictureId = $db->escape( $pictureId );
    return $db->getSingleValue( "select picture_id from album_pictures where album_id=$qAlbumId and picture_id>$qPictureId order by picture_id asc limit 1" );
  }

  /**
  * Finds the nexts picture in an album.
  * @param $albumId [int] ID of the album
  * @param $pictureId [int] ID of the current picture
  * @return ID of the next picture (null if none)
  */
  public static function findNext( $albumId, $pictureId )
  {
    $db = $GLOBALS['db'];
    $qAlbumId = $db->escape( $albumId );
    $qPictureId = $db->escape( $pictureId );
    return $db->getSingleValue( "select picture_id from album_pictures where album_id=$qAlbumId and picture_id<$qPictureId order by picture_id desc limit 1" );
  }

  /**
  * Finds an album by title. Optionally creates a new album if none exists
  * by that title.
  * @param $title [string] title of the album
  * @param $create [boolean] (optional) create a new album if none found, defaults to false
  * @return Album loaded instance when found/created, otherwise a bare instance
  * @bug Only allows one album by title globally, this should be (at least) on a per-user basis.
  */
  public static function findByTitle( $title, $create=false )
  {
    $db = $GLOBALS['db'];

    // Find
    $qTitle = $db->escape($title);
    $q = "select id from albums where title='$qTitle'";
    $id = $db->getSingleValue($q);

    // Create if it doesn't exist
    if ( !$id && $create )
    {
      $q = "insert into albums (title) values ('$qTitle')";
      $rs = $db->query($q);
      $id = $db->lastInsertId($rs);
    }

    return new Album($id);
  }

}

?>