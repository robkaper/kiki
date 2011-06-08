<body>
<?
  include Template::file('header');
  include Template::file('nav');
  include Template::file('aside');
?>
<div id="cw"><div id="content">
  <h1><?= $this->title; ?></h1>
  <?= $this->content; ?>
</div></div>
<?
  if ( Config::$facebookApp )
  {
    include Template::file('facebook/connect');
  }

  if ( Config::$twitterApp && Config::$twitterAnywhere )
  {
    include Template::file('twitter/anywhere');
  }

  include Template::file('body/footer');
?>
<div id="jsonUpdate"></div>
</body>
