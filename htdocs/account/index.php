<?
  include_once "../../lib/init.php";

  $page = new Page( "Jouw Account" );
  $page->header();

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

  echo "<h2>Facebook</h2>\n";
?> 
<div id="facebookPermissions" class="jsonupdate">
<ul>
<?= Boilerplate::facebookPermissions( $user ); ?>
</ul>
</div>
<?
  $page->footer();
?>
