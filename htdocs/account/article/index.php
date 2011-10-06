<?
  require_once "../../lib/init.php";

  $page = new Page( "Create new article" );
  $page->header();

  if ( !$user->isAdmin() )
  {
    echo "<p>\nLeuk geprobeerd.</p>\n";
    $page->footer();
    exit();
  }
?>

<h2>New article</h2>
<?
  // echo Articles::showSingle( $db, $user, 0 );
  echo Articles::form( $user );
  echo Form::ajaxFileUpload();
?>
<?
  $page->footer();
?>