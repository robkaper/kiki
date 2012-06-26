<div id="album_<?= $this->id ?>" class="album">
  <div class="header"><span class="albumTitle"><?= $this->title ?></span>: <span class="pictureTitle"><?= $pictureTitle ?></span></div>
  <div class="imgw"><img id="<?= $imgId ?>" src="<?= $imgUrl ?>" /><br class="clear" />
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow<?= $leftClass; ?>" href="#"><img src="{$config.iconPrefix}/arrow_left_alt1_32x32.png" alt="&lt;" width="32" height="32" /></a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow<?= $rightClass; ?>" href="#"><img src="{$config.iconPrefix}/arrow_right_alt1_32x32.png" alt="&gt;" width="32" height="32" /></a></div>
  </div>
  <div class="bar"></div>
  <div><?= null; // Comments::show( $GLOBALS['db'], $GLOBALS['user'], 0 ); ?></div>
</div>
