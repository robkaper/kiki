<?php

/**
 * (Photo) album.
 *
 * @todo Support more media types (template currently assumes resources can
 * be used in an img element).
 *
 * @class Album
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Album
{
  private $db;

  public $id, $title;
  private $system;

  /**
   * Initialises an album.
   *
   * @param int $id ID of the album, for quick loading
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
    $this->system = false;
  }

  /**
   * Loads an album from the database.
   *
   * @param int $id ID of the album
   */
  public function load( $id )
  {
    $qId = $this->db->escape( $id );
    $q = "select id,title,system from albums where id=$qId";
    $o = $this->db->getSingle($q);
    $this->setFromObject($o);
  }
  
  public function setFromObject( $o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->title = $o->title;
    $this->system = $o->system;
  }

  public function save()
  {
    $this->id ? $this->update() : $this->insert();
  }

  public function insert()
  {
    $q = $this->db->buildQuery( "insert into albums (title,system) values ('%s', %d)", $this->title, $this->system );
    $rs = $this->db->query($q);
    $this->id = $this->db->lastInsertId($rs);
  }
  
  public function update()
  {
    $q = $this->db->buildQuery( "UPDATE albums set title='%s',system=%d WHERE id=%d", $this->title, $this->system, $this->id );
    $rs = $this->db->query($q);
  }

  /**
   * Provides the full URL of the album to be used in external references.
   *
   * @param int $pictureId ID of the picture to start with, if none is given
   * the most recent picture will be used
   * @return string full URL of the album
   */
  public function url( $pictureId=0 )
  {
    return "http://". $_SERVER['SERVER_NAME']. $this->uri($pictureId);
  }

  /**
   * Provides the local URI part of the album.
   *
   * @param int $pictureId ID of the picture to start with, if none is given
   * the most recent picture will be used
   * @return string local URI part of the album
   */
  public function uri( $pictureId=0 )
  {
    return Config::$kikiPrefix. "/album/$this->id/". ($pictureId ? "$pictureId/" : null);
  }

  /**
   * Generates the HTML for the album viewer.
   *
   * @param int $pictureId ID of the picture to start with, if none is given
   * the most recent picture will be used
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
      include Template::file( 'parts/album-empty' );
      return;
    }

    // Picture details
    $qPictureId = $this->db->escape($pictureId);
    $q = "select title, storage_id from pictures where id=$qPictureId";
    $o = $this->db->getSingle($q);

    $picture = array(
      'id' => $pictureId,
      'title' => $o->title,
      'url' => Storage::url($o->storage_id)
    );

    $template = new Template( 'parts/album' );

    $album = array(
      'id' => $this->id,
      'title' => $this->title,
      'prev' => self::findPrevious($this->id, $pictureId),
      'next' => self::findNext($this->id, $pictureId),
    );
      
    $template->assign( 'album', $album );
    $template->assign( 'objectId', 0 );
    $template->assign( 'comments', array() );

    return $template->fetch();
  }

  /**
   * Adds pictures to the album. All pictures get the same title/description,
   * if this is not desired this method must be called separately for each
   * picture.
   *
   * @todo don't use storage IDs but create an actual Picture class, this
   * method should only link the pictures into the album.
   *
   * @param string $title title of the picture(s)
   * @param string $description decription of the picture(s)
   * @param array $storageIds list of Storage IDs of the picture(s)
   * @return array the pictures (themselves an array: id, title, decription)
   * inserted
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
   *
   * @param int $albumId ID of the album
   * @param int $pictureId ID of the current picture
   *
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
   *
   * @param int $albumId ID of the album
   * @param int $pictureId ID of the current picture
   *
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
   *
   * @bug Only allows one album by title globally, this should be (at least)
   * on a per-user basis.
   *
   * @param string $title title of the album
   * @param boolean $create (optional) create a new album if none found,
   * defaults to false
   *
   * @return Album loaded instance when found/created, otherwise a bare
   * instance
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
      $album = new Album();
      $album->setTitle($title);
      $album->save();
      return $album;
    }

    return new Album($id);
  }

  public function formItem( $pictureId )
  {
    // Picture details
    $qPictureId = $this->db->escape($pictureId);
    $q = "select storage_id from pictures where id=$qPictureId";
    $o = $this->db->getSingle($q);
    $storageId = $o->storage_id;
    $imgUrl = Storage::url($storageId, 75, 75, true);

    return "<div class=\"pictureFormItem\" id=\"pictureFormItem_$pictureId\"><div class=\"img-overlay\"><a class=\"removePicture\" href=\"#\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\"></a></div><img src=\"$imgUrl\" alt=\"\"></div>";
    
    ob_start();
    include Template::file( 'forms/album-editpicture' );
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }

  public function form()
  {
    $q = "select p.id from pictures p, album_pictures ap where p.id=ap.picture_id and ap.album_id=$this->id order by ap.sortorder ASC, p.storage_id asc";
    $rs = $this->db->query($q);

    echo "<h2>Album</h2>";

    echo "<div id=\"albumForm_". $this->id. "\" class=\"albumForm\">";
    if ( $rs && $this->db->numRowS($rs) )
    {
      while( $o = $this->db->fetchObject($rs) )
      {
        // include Template::file('forms/album-editpicture' );
        echo $this->formItem($o->id);
      }
    }
          
    echo "</div>";

    echo "<div class=\"ui-dialog\" id=\"pictureDeleteConfirm\" title=\"Delete picture?\"><p>Are you sure you want to delete this picture?</p></div>";

    include Template::file('forms/album-newpicture' );
    return;

    // UNUSED
    $q = "select hash,extension from storage where id not in (select storage_id from pictures)";
    $rs = $this->db->query($q);
    if ( $rs && $this->db->numRowS($rs) )
    {
      while( $o = $this->db->fetchObject($rs) )
      {
        echo "<pre>". print_r($o, true). "</pre>". PHP_EOL;
        echo "<img src=\"/storage/". $o->hash. ".100x100.c.". $o->extension. "\" style=\"width: 100px; height: 100px; float: left;\">";
      }
      echo "<br class=\"spacer\">";
    }
  }

  public function id() { return $this->id; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setSystem( $system ) { $this->system  = $system; }
  public function system() { return $this->system; }
}

?>