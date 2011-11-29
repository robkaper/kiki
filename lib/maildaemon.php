<?

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
    $o = $this->mailerQueue->getNext( "lock_". $this->pid );
    if ( !$o )
      return 5000000;

    $email = new Email( $o->from, $o->to, $o->subject, null, null );
    $email->setHeaders( $o->headers );
    $email->setBody( $o->body );

    Mailer::smtp( $email );
    $this->mailerQueue->markSent( $o->id );
    return 0;
  }
    
  protected function cleanup($pid)
  {
    echo "cleaning up pid $pid\n";
    $q = $this->db->buildQuery( "update mail_queue set lock_id=null where lock_id='lock_%d'", $pid );
    $this->db->query($q);  
  }
}

?>