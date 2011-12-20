<body>
<?
  include Template::file('page/body/header');
  include Template::file('page/body/nav');
  include Template::file('page/body/aside-admin');
  include Template::file('page/body/aside');
  
?>
<div id="cw" class="twosides"><div id="content">
  <h1><?= $this->title; ?></h1>
  <?= $this->content; ?>
</div></div>
<?
  if ( Config::$facebookApp )
  {
    // TODO: consider re-enabling, although we prefer explicit logins
    // include Template::file('facebook/connect');
  }

  if ( Config::$twitterApp && Config::$twitterAnywhere )
  {
    // TODO: consider re-enabling, although we prefer explicit logins
    // include Template::file('twitter/anywhere');
  }

  include Template::file('page/body/footer');
?>
<div id="jsonUpdate"></div>
</body>
