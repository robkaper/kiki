#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = $argv[1];
  require_once str_replace( "bin/mailer-daemon.php", "lib/init.php", __FILE__ );

  if ( !Config::$mailerQueue )
  {
    echo "Won't run: Config::\$mailerQueue is false\n";
    exit();
  }

  $maild = new MailDaemon();
  $maild->start(1);
?>