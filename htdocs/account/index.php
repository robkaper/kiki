<?
  include_once "../../lib/init.php";

  $page = new Page( "Jouw Account" );
  $page->header();

  if ( Config::$twitterApp )
  {
    echo "<h2>Twitter</h2>\n";

    echo "<ul>\n";
    if ( $user->twUser->authenticated )
    {
      echo "<li>Je bent ingelogd als <strong>". $user->twUser->screenName. "</strong> (". $user->twUser->name. ") (<a href=\"/proclaimer.php#disconnect\">Ontkoppelhulp</a>).</li>\n";
      echo "<li>Deze site heeft schrijfrechten. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";
      echo "<li>Deze site heeft offline toegang. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</li>\n";
    }
    else
      echo "<li>Je bent niet ingelogd.</li>\n";
    echo "</ul>\n";
  }

  if ( Config::$facebookApp )
  {
    echo "<h2>Facebook</h2>\n";
?> 
<div id="facebookPermissions" class="jsonupdate">
<?= Boilerplate::facebookPermissions( $user ); ?>
</div>
<?
  }

  if ( User::anyUser() )
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

      list( $localPart, $domain ) = split( "@", Config::$mailToSocialAddress );
      $mailToSocialUserAddress = $localPart. "+". $user->mailAuthToken. "@". $domain;
      echo "<li>Update je status en foto's door ze te e-mailen naar <a href=\"mailto:$mailToSocialUserAddress\">$mailToSocialUserAddress</a></li>\n";
    }

    echo "</ul>\n";
  }

  $page->footer();
?>
