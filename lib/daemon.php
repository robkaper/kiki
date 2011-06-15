<?

declare( ticks = 1 );

/**
* @class Daemon
* Extendable base class for daemon processess.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
* @todo Document class.
* @bug Mixed use of syslog and Log class, choose or merge syslog functionality into Log class.
* @bug Uses PID file, but doesn't check it... also, requires root.
* @warning Taken from my general code collection and not thoroughly integrated into Kiki yet.
*/
  
abstract class Daemon
{
  private $db;
  private $name;
  private $logFacility;
  protected $pid = 0;
  private $childPids = array();
  private $killPids = array();
  private $shutdown = false;
  private $killTime = 0;

  public function __construct( $name, $logFacility=LOG_DAEMON )
  {
    $this->db = $GLOBALS['db'];
    $this->name = $name;
    $this->logFacility = $logFacility;

    $this->pid = getmypid();

    openlog( "$name", LOG_PID, $this->logFacility );
  }

  /// @warning Historically implemented a loop itself, but this class should handle that itself.
  abstract protected function main();
  abstract protected function cleanup( $pid );
    
  public function start( $numChildren=3 )
  {
    $this->setHandlers();
    Log::info( "started" );
    
    /// Prior to forking children, otherwise they end up as orphans.
    // $this->gotoBg();

    for( $i=0 ; $i<$numChildren ; $i++ )
    {
      $pid = $this->createChild();
      if ( $pid > 0 )
        $this->childPids[]=$pid;
    }

    $this->run();
  }

  private function setHandlers()
  {
    pcntl_signal( SIGTERM, array(&$this, "signalHandler") );
  }

  public function signalHandler( $signal )
  {
    switch( $signal )
    {
    case SIGTERM:
      Log::info( "received SIGTERM" );
      if ( $count = count($this->childPids) )
      {
        // Parent
        $this->shutdown = true;
        $this->killTime = time()+5;
        $this->reapChildren();
      }
      else
      {
        // Child
        $this->shutdown = true;
      }
      break;
    default:;
    }
  }

  private function reapChildren( $kill = false )
  {
    for( $i=0 ; $i<count($this->childPids) ; ++$i )
    {
      $pid = $this->childPids[$i];
      $rv = posix_kill($pid, ($kill ? SIGKILL : SIGTERM) );
      if ( $kill )
        $this->killPids[] = $pid;
      if ( !$rv )
      {
        $signal = $kill ? "SIGKILL" : "SIGTERM";
        Log::error( "signal $signal to child $pid failed" );
      }
    }
  }

  private function gotoBg()
  {
    // Fork a child and exit parent process: this is a daemon. Child continues.
    $pid = pcntl_fork();
    if ( $pid == -1)
    {
      Log::error( "fork to background failed" );
      exit(1);
    }
    else if ( $pid )
    {
      $fp = fopen( "/var/run/$this->name.pid", "w" );
      if ( $fp )
      {
        fwrite( $fp, "$pid\n" );
        fclose( $fp );
      }
      else
        Log::info( "could not write PID file!" );

      exit(0);
    }
  }

  protected function run()
  {
    $timeToKill = -1;
    for(;;)
    {
      for( $i=0 ; $i<count($this->childPids) ; ++$i )
      {
        $pid = $this->childPids[$i];

        $rv = pcntl_waitpid( $pid, $status, WNOHANG );
        if ( $rv == -1 )
        {
          $ok = pcntl_wifexited( $status );
          if ( $ok )
            Log::info( "child $pid exited" );
          else
          {
            Log::error( "error with child $pid" );
            /// @todo cleanup() here?
          }

          array_splice( $this->childPids, $i, 1 );
          if ( !$this->shutdown )
          {
            $pid = $this->createChild();
            if ( $pid > 0 )
              $this->childPids[] = $pid;
          }
          --$i; continue;
        }
      }
        
      $count = count($this->childPids);
      if ( $count )
      {
        if ( $this->shutdown )
        {
          $newTimeToKill = $this->killTime - time();
          Log::info( "ttk: $timeToKill, nttk: $newTimeToKill, kt: $this->killTime" );
          if ( $newTimeToKill > 0 && $newTimeToKill != $timeToKill )
          {
            $timeToKill = $newTimeToKill;
            Log::info( "waiting $timeToKill seconds for $count children to exit" );
          }
          else if ( $newTimeToKill == 0 )
          {
            $this->reapChildren( 1 );
          }
        }
        sleep(1);
      }
      else
      {
        for( $i=0 ; $i<count($this->killPids) ; ++$i )
        {
          $pid = $this->killPids[$i];
          Log::info( "cleaning up for pid=[$pid]" );
          $this->cleanup( $pid );
        }
        unlink( "/var/run/$this->name.pid" );
        return;
      }
    }
  }

  function createChild()
  {
    $pid = pcntl_fork();

    if ( $pid == -1 )
      Log::error( "fork failed" );
    else if ( $pid )
    {
      Log::info( "forked a child, pid=$pid" );
      // pcntl_wait($status); // Protect against Zombie children
    }
    else
    {
      // Forked child starts execution here.
      $this->pid = getmypid();

      // Reset list of children, as a child we have none.
      if ( count($this->childPids) )
        $this->childPids = array();

      for( ;; )
      {
        $this->main();

        if ( $this->shutdown )
          exit();

        sleep(1);
      }
      exit();
    }
    return $pid;
  }
}

?>