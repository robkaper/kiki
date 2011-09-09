<body>
<?
  include Template::file('page/body/header');
?>
<div id="cw" class="noaside"><div id="content" lang="en">

<h1>Kiki says: <q>mea culpa</q>.</h1>

<p>
This website has no content for you here.</p>

<ul>
<li>This particular page does not exist.</li>
<li>Perhaps it never has existed..</li>
<li>Maybe it hasn't been created yet..</li>
<li>Or something is misconfigured..</li>
</ul>

<h2>Try one of these</h2>

<ul>
<li><a href="/">Homepage</a></li>
</ul>

<?
  $articles = Router::getBaseUris( 'articles', true );
  if ( count($articles) )
  {
    echo "<h3>News, blog and article collections</h3>\n";
    echo "<ul>";
    foreach( $articles as $baseUri => $article )
      echo "<li><a href=\"$baseUri/\">". substr( $baseUri, 1 ). "</a></li>\n";
    echo "</ul>";
  }
?>

<h2>Expecting a number?</h2>

<ul>
<li>four hundred and four!</li>
<li lang="nl">vierhonderdenvier!</li>
<li lang="de">hier hundert und vier!</li>
<li lang="es">cuatrocientoscuatro!</li>
<li lang="hu">n&eacute;gysz&aacute;mn&eacute;vn&eacute;!</li>
<li lang="fi">nelj&auml;sataanelj&auml;!</li>
</ul>

</div></div>
<?
  if ( Config::$facebookApp )
  {
    // @todo consider re-enabling, although we prefer explicit logins
    // include Template::file('facebook/connect');
  }

  if ( Config::$twitterApp && Config::$twitterAnywhere )
  {
    // @todo consider re-enabling, although we prefer explicit logins
    // include Template::file('twitter/anywhere');
  }

  include Template::file('page/body/footer');
?>
<div id="jsonUpdate"></div>
</body>
