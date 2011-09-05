<body>
<?
  include Template::file('page/body/header');
  include Template::file('page/body/nav');
  include Template::file('page/body/aside');
?>
<div id="cw"><div id="content">
  <h1><?= $this->title; ?></h1>
  <?= $this->content; ?>
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
