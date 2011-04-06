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
    // FIXME: rjkcust, include dryicons in Kiki
?>
<div id="album_<?= $id ?>" class="album">
  <div class="header"><?= $title ?></div>
  <div class="imgw"><?= $img ?><br class="clear" />
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow" href="#"><img src="/static/img/dryicons/32x32/left_arrow.png" alt="&gt;" width="32" height="32" /></a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow" href="#"><img src="/static/img/dryicons/32x32/right_arrow.png" alt="&gt;" width="32" height="32" /></a></div>
  </div>
  <div class="bar">[// TODO: tag/like bar]</div>
  <div><?= Comments::show( $GLOBALS['db'], $GLOBALS['user'], 0 ); ?></div>
</div>
<?
  }
}

?>