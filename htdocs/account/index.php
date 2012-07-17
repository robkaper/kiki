<?
  $this->template = $user->isAdmin() ? 'pages/admin' : 'pages/default';
  $this->title = _("Your Account");

  ob_start();

  foreach( $user->connections() as $connectedUser )
  {
    // include Template::file( 'parts/connections/account-info');
  }

?> 
<div id="facebookPermissions" class="jsonupdate">
<?= Boilerplate::facebookPermissions( $user ); ?>
</div>
<?
  if ( $user->anyUser() )
  {
    echo "<h2>Sociale hulpmiddelen</h2>\n";
    echo "<ul>\n";
    echo "<li><a href=\"social.php\">Update status</a></li>\n";

    if ( $emailUploadAddress = $user->emailUploadAddress() )
    {
      echo "<li>Update je status en foto's door ze te e-mailen naar:<br /><a href=\"mailto:$emailUploadAddress\">$emailUploadAddress</a></li>\n";
    }

    echo "</ul>\n";
  }
  else
  {
    echo "<p>\nLogin first.</p>\n";
  }

  $this->content = ob_get_clean();
?>
