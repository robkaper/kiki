<div id="album_{$album.id}" class="album">
  <div class="header"><span class="albumTitle">{$album.title}</span>: <span class="pictureTitle">{$picture.title}</span></div>
  <div class="imgw"><img id="{$picture.id}" src="{$picture.url}"><br class="spacer">
    <div id="navleftw" class="navarroww"><a id="navleft" class="navarrow{if !$album.prev} disabled{/if}" href="#">&lt;</a></div>
    <div id="navrightw" class="navarroww"><a id="navright" class="navarrow{if !$album.next} disabled{/if}" href="#">&gt;</a></div>
  </div>
  <div class="bar"></div>
  <div>{*include 'parts/comments'*}</div>
</div>
