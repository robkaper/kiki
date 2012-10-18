<div id="album_{$album.id}" class="album">
  <div class="header"><span class="albumTitle">{$album.title}</span>: <span class="pictureTitle">{$picture.title}</span></div>
  <div class="imgw"><img id="{$picture.id}" src="{$picture.url}"><br class="spacer">
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow{if !$album.prev} disabled{/if}" href="#"><img src="{$config.iconPrefix}/arrow_left_alt1_32x32.png" alt="&lt;" width="32" height="32"></a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow{if !$album.next} disabled{/if}" href="#"><img src="{$config.iconPrefix}/arrow_right_alt1_32x32.png" alt="&gt;" width="32" height="32"></a></div>
  </div>
  <div class="bar"></div>
  <div>{*include 'parts/comments'*}</div>
</div>
