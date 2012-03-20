<p><label><?= $label; ?></label>
<?
  $imgUrl = $selected ? Storage::url($selected) : "/kiki/img/blank.gif";
?>
<a href="#" class="albumSelectImageToggle"><img class="selectedImage" src="<?= $imgUrl; ?>" style="width: 75px; height: 75px; margin-top: 0.5em;" /></a>
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
<?

global $db;
$q = $db->buildQuery( "select p.storage_id from pictures p, album_pictures ap where ap.picture_id=p.id AND ap.album_id=%d", $albumId );
$rs = $db->query($q);
if ( $rs && $db->numRows($rs) )
{
  while( $o = $db->fetchObject($rs) )
  {
    $url = Storage::url($o->storage_id);
    echo "<a href=\"#\"><img id=\"picture_". $o->storage_id. "\" src=\"$url\" style=\"float: left; width: 75px; height: 75px; margin: 0 0.5em 0.5em 0;\" /></a>";
  }
}
else
  echo "<p class=\"noImages\">Upload eerst een foto in het album.</p>\n";
?>

</div>
<br style="clear: left"/>
</div>
</p>

<?= Form::hidden( $id, $selected ); ?>
