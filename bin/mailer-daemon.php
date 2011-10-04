#!/usr/bin/php
<?
  $_SERVER['SERVER_NAME'] = $argv[1];
  include_once str_replace( "bin/mailer-daemon.php", "lib/init.php", __FILE__ );

  if ( !Config::$mailerQueue )
  {
    echo "Won't run: Config::\$mailerQueue is false\n";
    exit();
  }

  // FIXME: check if already running, only clean up database when we're not running..
  $db->query( "update mail_queue set lock_id=null" );

  class MailDaemon extends Daemon
  {
    private $mailerQueue = null;

    public function __construct( $name = "kiki-maild", $logFacility = LOG_DAEMON )
    {
      parent::__construct( $name, $logFacility );
    }

    protected function childInit()
    {
      $this->mailerQueue = new MailerQueue($this->db);
    }

    protected function main()
    {
      Log::debug( "main for $this->pid" );
      $o = $this->mailerQueue->getNext( "lock_". $this->pid );
      if ( !$o )
        return 5000000;

      $email = new Email( $o->from, $o->to, $o->subject, null, null );
      $email->setHeaders( $o->headers );
      $email->setBody( $o->body );

      Mailer::smtp( $email );
      $this->mailerQueue->markSent( $o->id );

      Log::debug( "end main for $this->pid" );
      return 0;
    }
    
    protected function cleanup($pid)
    {
      echo "cleaning up pid $pid\n";
    }
  }

  $maild = new MailDaemon();
  $maild->start(1);

  // $db->query( "update mail_queue set sent=0,lock_id=null" );    
?>