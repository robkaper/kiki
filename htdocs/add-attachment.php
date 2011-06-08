<?
  include_once "../lib/init.php";

  $tmpFile = $_FILES['attachment']['tmp_name'];
  $name = $_FILES['attachment']['name'];
  $size = $_FILES['attachment']['size'];
  $target = $_POST['target'];

  $id = $tmpFile ? Storage::save( $name, file_get_contents($tmpFile) ) : 0;

  // addAttachment is defined in htdocs/scripts/default.js
?>
<script type="text/javascript">
window.parent.addAttachment( '<?= $target; ?>', '<?= $id; ?>', '<?= Storage::url($id); ?>' );
</script>
