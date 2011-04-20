<?
  include_once "../lib/init.php";

  $page = new Page( "Kiki CMS Status" );
  $page->header();

  echo "<h2>PHP modules and PEAR/PECL extensions</h2>\n";

  $extensions = array( 'curl' => null, 'mysql' => null , 'Mailparse (PECL)' => 'mailparse_msg_create' );

  echo "<ul>\n";
  foreach( $extensions as $extension => $function )
  {
    if ( $function )
      $loaded = function_exists($function);
    else
      $loaded = extension_loaded( $extension );
    $loadedStr = $loaded ? "enabled" : "<span style=\"color: red\">disabled</span>";
    echo "<li><strong>$extension</strong>: ${loadedStr}.</li>\n";
  }
  echo "</ul>\n";

  $page->footer();
?>
