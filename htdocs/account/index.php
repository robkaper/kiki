<?
  include_once "../../lib/init.php";

  $page = new Page( "Jouw Account" );
  $page->header();

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

    if ( Config::$mailToSocialAddress )
    {
      if ( !$user->mailAuthToken )
      {
        // TODO: salt, pepper, re-hash... this is only moderately secure
        $user->mailAuthToken = sha1( uniqid(). $user->id );
        $db->query( "update users set mail_auth_token='$user->mailAuthToken' where id=$user->id" );
      } 

      list( $localPart, $domain ) = explode( "@", Config::$mailToSocialAddress );
      $mailToSocialUserAddress = $localPart. "+". $user->mailAuthToken. "@". $domain;
      echo "<li>Update je status en foto's door ze te e-mailen naar <a href=\"mailto:$mailToSocialUserAddress\">$mailToSocialUserAddress</a></li>\n";
    }

    echo "</ul>\n";
  }
  else
  {
    echo "<p>\nLogin first.</p>\n";
  }

  $page->footer();
?>
