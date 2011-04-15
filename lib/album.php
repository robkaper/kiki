<?

class Album
{
  
  public function showAlbum( &$db, $id )
  {
    $photos = array();

    // TODO: album (and picture) data model
    $q = "select id from storage order by id asc limit 1";
    $o = $db->getSingle($q);

    $title = null; // "Album title: picture title";
    $imgId = $o->id;
    $imgUrl = Storage::url($o->id);
    $img = "<img id=\"$imgId\" src=\"$imgUrl\" />";

    // TODO: navleft/right only showing when available
?>
<div id="album_<?= $id ?>" class="album">
  <div class="header"><?= $title ?></div>
  <div class="imgw"><?= $img ?><br class="clear" />
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow" href="#"><img src="<?= Config::$iconPrefix ?>/arrow_left_alt1_32x32.png" alt="&lt;" width="32" height="32" /></a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow" href="#"><img src="<?= Config::$iconPrefix ?>/arrow_right_alt1_32x32.png" alt="&gt;" width="32" height="32" /></a></div>
  </div>
  <div class="bar">[// TODO: tag/like bar]</div>
  <div><?= Comments::show( $GLOBALS['db'], $GLOBALS['user'], 0 ); ?></div>
</div>
<?
  }
}

?>