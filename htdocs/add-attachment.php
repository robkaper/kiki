<?
  include_once "../../lib/init.php";

  $tempFile = $_FILES['attachment']['tmp_name'];
  $name = $_FILES['attachment']['name'];
  $size = $_FILES['attachment']['size'];
  $target = $_POST['target'];

  // FIXME: actually store attachment, assign proper URI, relay URI
  $fileUri = uniqid();
?>
<script type="text/javascript">
window.parent.addAttachment( '<?= $target; ?>', '<?= $fileUri; ?>' );
</script>
