<?
/**
 * Handles Ajax file upload requests (POSTS) and calls fileUploadHandler
 * from default.js in parent window (Ajax uploads use iframes).
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  require_once "../lib/init.php";

  $tmpFile = $_FILES['attachment']['tmp_name'];
  $name = $_FILES['attachment']['name'];
  $size = $_FILES['attachment']['size'];
  $target = $_POST['target'];

  $id = $tmpFile ? Storage::save( $name, file_get_contents($tmpFile) ) : 0;

  $html = null;

  $albumId = isset($_POST['albumId']) ? $_POST['albumId'] : null;
  if ( $albumId && $id )
  {
    $album = new Album($albumId);
    $pictures = $album->addPictures( null, null, array($id) );

    $html = $album->formItem($pictures[0]['id']);
  }

  // fileUploadHandler is defined in htdocs/scripts/default.js
?>
<script type="text/javascript">
window.parent.fileUploadHandler( '<?= $target; ?>', '<?= $id; ?>', '<?= Storage::url($id); ?>', <?= json_encode($html); ?> );
</script>
