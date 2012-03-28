<body>
<?
  include Template::file('parts/header');
  include Template::file('parts/nav');
  include Template::file('parts/aside');
?>
<div id="cw"><div id="content">
  <h1><?= $this->title; ?></h1>
  <?= $this->content; ?>
</div></div>
<?
  include Template::file('parts/footer');
?>
<div id="jsonUpdate"></div>
</body>
