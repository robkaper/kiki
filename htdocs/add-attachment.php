<?
  include_once "../lib/init.php";

  $tmpFile = $_FILES['attachment']['tmp_name'];
  $name = $_FILES['attachment']['name'];
  $size = $_FILES['attachment']['size'];
  $target = $_POST['target'];

  if ( $tmpFile )
    $id = Storage::save( $name, file_get_contents($tmpFile) );
  else
    $id = 0;
?>
<script type="text/javascript">
window.parent.addAttachment( '<?= $target; ?>', '<?= $id; ?>', '<?= Storage::url($id); ?>' );
</script>
