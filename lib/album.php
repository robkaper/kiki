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

namespace Kiki;

class Album extends BaseObject
{
  public $title;
  private $system;

  private $highlight_id;

  /**
   * Resets member variables to their pristine state.
   */
  public function reset()
  {
		parent::reset();

    $this->title = null;
    $this->system = false;

    $this->highlight_id = null;
  }

  /**
   * Loads an album from the database.
   *
   * @param int $id ID of the album
   */
  public function load( $id = 0 )
  {
		if ( $id )
		{
			$this->id = $id;
			$this->object_id = 0;
		}

		$qFields = "id, title, system, highlight_id, o.object_id, o.ctime, o.mtime, o.section_id, o.user_id, o.visible";
		$qFields = "id, title, system, highlight_id, o.object_id, o.ctime, o.mtime, o.user_id, title, system";
		$q = $this->db->buildQuery( "SELECT $qFields FROM albums a LEFT JOIN objects o ON o.object_id=a.object_id WHERE a.id=%d OR o.object_id=%d", $this->id, $this->object_id );
		$this->setFromObject( $this->db->getSingleObject($q) );
  }
  
  public function setFromObject( $o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->title = $o->title;
    $this->system = $o->system;
    
    $this->highlight_id = $o->highlight_id;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $qHighLightId = Database::nullable($this->highlight_id);

    $q = $this->db->buildQuery(
      "UPDATE albums set object_id=%d, title='%s', system=%d, highlight_id=%s WHERE id=%d",
      $this->object_id, $this->title, $this->system, $qHighLightId, $this->id
    );

    $this->db->query($q);
  }

  public function dbInsert()
  {
    $qHighLightId = Database::nullable($this->highlight_id);

    $q = $this->db->buildQuery(
      "INSERT INTO albums (object_id, title, system, highlight_id) VALUES (%d, '%s', %d, %s)",
      $this->object_id, $this->title, $this->system, $qHighLightId
    );

    $rs = $this->db->query($q);
    
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
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
			$pictureId = $this->firstPicture();
    }

    if ( !$pictureId )
    {
			$template = new Template( 'parts/album-empty' );
			return $template->fetch();
    }

    // Picture details
    $q = $this->db->buildQuery( "SELECT title, storage_id FROM pictures WHERE id=%d", $pictureId );
    $o = $this->db->getSingleObject($q);

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
			'picture' => $picture
    );
      
    $template->assign( 'album', $album );
    $template->assign( 'picture', $picture );
    $template->assign( 'object_id', 0 );
    $template->assign( 'comments', array() );

    return $template->fetch();
  }

	public function imageUrls()
	{
    $q = "select p.storage_id as id from pictures p, album_pictures ap where p.id=ap.picture_id and ap.album_id=$this->id order by ap.sortorder ASC, p.storage_id asc";
		$storageIds = $this->db->getObjectIds($q);
		// echo "<pre>";
		// echo $q;
		// print_r( $storageIds );

		$urls = array();
		foreach( $storageIds as $storageId )
			$urls[] = Storage::url($storageId, 724, 406, true );
		// print_r( $urls );

		return $urls;		
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
      $q = "INSERT INTO `pictures` (title, description, storage_id) VALUES ('%s', '%s', %d)";
      $q = $this->db->buildQuery( $q, $title, $description, $storageId );
      $rs = $this->db->query($q);
      $pictures[]= array( 'id' => $this->db->lastInsertId($rs), 'storage_id' => $storageId, 'title' => $title, 'description' => $description );
    }

    // Link pictures into album
    foreach( $pictures as $picture )
    {
      $pictureId = $picture['id'];
      $q = "insert into album_pictures (album_id,picture_id) values ($this->id, $pictureId)";
      $q = $this->db->buildQuery( "INSERT INTO album_pictures (album_id, picture_id, sortorder) SELECT %d, %d, IFNULL(MAX(sortorder+1), 1) FROM album_pictures WHERE album_id=%d", $this->id, $pictureId, $this->id );
      $this->db->query($q);
    }

    return $pictures;
  }

  // FIXME: this should honour sortorder
  public function firstPicture()
  {
    $q = "select p.id from pictures p, album_pictures ap where p.id=ap.picture_id and ap.album_id=$this->id order by p.storage_id desc limit 1";
    return $this->db->getSingleValue($q);
  }

  public function setHighlightId( $pictureId )
  {
    $this->highlight_id = $pictureId;
  }

  public function getHighlightId()
  {
    return $this->highlight_id;
  }
  
  /**
   * Finds the previous picture in an album.
   *
   * @param int $albumId ID of the album
   * @param int $pictureId ID of the current picture
   *
   * @return ID of the previous picture (null if none)
   */
  // FIXME: this should honour sortorder
  public static function findPrevious( $albumId, $pictureId )
  {
    $db = Core::getDb();
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
  // FIXME: this should honour sortorder
  public static function findNext( $albumId, $pictureId )
  {
    $db = Core::getDb();
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
    $db = Core::getDb();

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
    $o = $this->db->getSingleObject($q);
    $storageId = $o->storage_id;
    $imgUrl = Storage::url($storageId, 75, 75, true);

    return "<div class=\"pictureFormItem\" id=\"pictureFormItem_$pictureId\"><div class=\"img-overlay\"><a class=\"removePicture\" href=\"#\">Delete</a></div><img src=\"$imgUrl\" alt=\"\"></div>";
    
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

  public function setTitle( $title )
  {
    $this->title = $title;
    $this->object_name = $title;
  }

  public function title() { return $this->title; }
  public function setSystem( $system ) { $this->system  = $system; }
  public function system() { return $this->system; }

}
