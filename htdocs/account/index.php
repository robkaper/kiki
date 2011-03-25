<?
  include_once "../../lib/init.php";

  $page = new Page( "Jouw Account" );
  $page->header();

  echo "<h2>Twitter</h2>\n";

  if ( $user->twUser->authenticated )
  {
    echo "<p>Je bent ingelogd als <strong>". $user->twUser->screenName. "</strong> (". $user->twUser->name. ").</p>\n";
    echo "<p>Deze site heeft schrijfrechten. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</p>\n";
    echo "<p>Deze site heeft offline toegang. (<a href=\"/proclaimer.php#twitter\">waarom?</a>)</p>\n";
  }
  else
    echo "<p>Je bent niet ingelogd.</p>\n";

  echo "<h2>Facebook</h2>\n";
?> 
<div id="facebookPermissions" class="jsonupdate">
<?= Boilerplate::facebookPermissions( $user ); ?>
</div>
<?
  $page->footer();
?>
