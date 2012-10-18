<p><label>{$label}</label>
<a href="#" class="albumSelectImageToggle"><img class="selectedImage" src="{$imgUrl}" style="width: 75px; height: 75px; margin-top: 0.5em;"></a>
<div class="albumSelectImage" style="display: none;">

<script type="text/javascript">
$('.albumSelectImage > .imageList img').live( 'click', function() {
  var pictureId = $(this).attr('id').replace( /^picture_/, '' );
  $('input[name=headerImage]').val( pictureId );
  $('img.selectedImage').attr('src', $(this).attr('src') );
  $('.albumSelectImage').slideToggle();
  return false;
} );

$('.albumSelectImageToggle').live('click', function() {
  $('.albumSelectImage').slideToggle();
  return false;
} );
</script>

<div class="imageList">
{if $images|count}
  {foreach $images as $image}
    <a href="#"><img id="{$image.storageId}" src="{$image.url}" style="float: left; width: 75px; height: 75px; margin: 0 0.5em 0.5em 0;"></a>
  {/foreach}
{else}
  <p class="noImages">Upload eerst een foto in het album.</p>
{/if}
</div>
<br style="clear: left">

</div>
</p>

<input type="hidden" name="{$id}" value="{$selected}">
