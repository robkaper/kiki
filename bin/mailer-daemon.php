#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  if ( !Config::$mailerQueue )
  {
    echo "Won't run: Config::\$mailerQueue is false\n";
    exit();
  }

  $maild = new MailDaemon();
  $maild->start(1);
?>