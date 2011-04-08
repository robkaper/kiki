<?
  include_once "../../lib/init.php";

  $tempFile = $_FILES['attachment']['tmp_name'];
  $name = $_FILES['attachment']['name'];
  $size = $_FILES['attachment']['size'];
  $target = $_POST['target'];

  $id = Storage::save( $name, file_get_contents($tmpFile) );
?>
<script type="text/javascript">
window.parent.addAttachment( '<?= $target; ?>', $id, '<?= Storage::url($id); ?>' );
</script>
