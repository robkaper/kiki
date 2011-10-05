#!/usr/bin/php
<?
  $_SERVER['SERVER_NAME'] = $argv[1];
  include_once str_replace( "bin/mailer-daemon.php", "lib/init.php", __FILE__ );

  if ( !Config::$mailerQueue )
  {
    echo "Won't run: Config::\$mailerQueue is false\n";
    exit();
  }

  $maild = new MailDaemon();
  $maild->start(1);
?>