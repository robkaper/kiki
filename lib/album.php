<?

class Album
{
  private $db;

  public $id, $title;

  public function __construct( $id = null )
  {
    $this->db = $GLOBALS['db'];
    
    $this->reset();
    
    if ( $id )
      $this->load( $id );
  }

  public function reset()
  {
    $this->id = 0;
    $this->title = null;
  }

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

  public function show( $pictureId=0 )
  {
    echo "<hr />Album::show [id:$this->id][pictureId:$pictureId]";
    $photos = array();

    // Find latest if not starting picture specified
    if ( !$pictureId )
    {
      $q = "select p.id from pictures p, album_pictures ap where p.id=ap.picture_id and ap.album_id=$this->id order by p.storage_id desc limit 1";
      $pictureId = $this->db->getSingleValue($q);
    }
    echo "<hr />Album::show [id:$this->id][pictureId:$pictureId]";

    if ( !$pictureId )
      echo "<p>\nNo pictures in this album.</p>\n";

    // Picture details
    $qPictureId = $this->db->escape($pictureId);
    $q = "select title, storage_id from pictures where id=$qPictureId";
    $o = $this->db->getSingle($q);
    $pictureTitle = $o->title;
    $storageId = $o->storage_id;

    $title = "<span class=\"albumTitle\">$this->title</span>: <span class=\"pictureTitle\">$pictureTitle</span>";

    $imgId = $pictureId;
    $imgUrl = Storage::url($storageId);
    $img = "<img id=\"$imgId\" src=\"$imgUrl\" />";

    $leftClass = self::findPrevious( $this->id, $imgId ) ? null : " disabled";
    $rightClass = self::findNext( $this->id, $imgId ) ? null : " disabled";
?>
<div id="album_<?= $this->id ?>" class="album">
  <div class="header"><?= $title ?></div>
  <div class="imgw"><?= $img ?><br class="clear" />
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow<?= $leftClass; ?>" href="#"><img src="<?= Config::$iconPrefix ?>/arrow_left_alt1_32x32.png" alt="&lt;" width="32" height="32" /></a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow<?= $rightClass; ?>" href="#"><img src="<?= Config::$iconPrefix ?>/arrow_right_alt1_32x32.png" alt="&gt;" width="32" height="32" /></a></div>
  </div>
  <div class="bar">[// TODO: tag/like bar]</div>
  <div><?= Comments::show( $GLOBALS['db'], $GLOBALS['user'], 0 ); ?></div>
</div>
<?
  }

  public static function findPrevious( $albumId, $pictureId )
  {
    $db = $GLOBALS['db'];
    $qAlbumId = $db->escape( $albumId );
    $qPictureId = $db->escape( $pictureId );
    return $db->getSingleValue( "select picture_id from album_pictures where album_id=$qAlbumId and picture_id>$qPictureId order by picture_id asc limit 1" );
  }

  public static function findNext( $albumId, $pictureId )
  {
    $db = $GLOBALS['db'];
    $qAlbumId = $db->escape( $albumId );
    $qPictureId = $db->escape( $pictureId );
    return $db->getSingleValue( "select picture_id from album_pictures where album_id=$qAlbumId and picture_id<$qPictureId order by picture_id desc limit 1" );
  }

  public static function findByTitle( $title, $create=false )
  {
    $db = $GLOBALS['db'];

    $qTitle = $db->escape($title);
    $q = "select id from albums where title='$qTitle'";
    $id = $db->getSingleValue($q);

    if ( !$id && $create )
    {
      // Create
      $q = "insert into albums (title) values ('$qTitle')";
      $rs = $db->query($q);
      $id = $db->lastInsertId($rs);
    }

    return new Album($id);
  }

}

?>