<?php

declare( ticks = 1 );

/**
 * Abstract class for daemon processes.
 *
 * @bug Mixed use of syslog and Log class, choose or merge syslog functionality into Log class.
 * @bug Uses PID file, but doesn't check it... also, requires root.
 *
 * @class Daemon
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

abstract class Daemon
{
  protected $db = null;
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

  abstract protected function childInit();
  abstract protected function main();
  abstract protected function cleanup( $pid );
    
  public function start( $numChildren=3 )
  {
    $this->closeFileHandles();
    $this->setHandlers();

    // Prior to forking children, otherwise they end up as orphans.
    $this->toBackground();
    $this->detach();
    
    Log::info( "started" );

    for( $i=0 ; $i<$numChildren ; $i++ )
    {
      $pid = $this->createChild();
      if ( $pid > 0 )
        $this->childPids[]=$pid;
    }

    $this->run();
  }

  private function closeFileHandles()
  {
    fclose(STDIN);
    fclose(STDOUT);
    fclose(STDERR);
  }

  private function detach()
  {
    $rv = posix_setsid();
    if ( $rv === -1 )
    {
      Log::error( "detach failed" );
      die();
    }
  }

  private function setHandlers()
  {
    pcntl_signal( SIGTERM, array(&$this, "signalHandler") );
    pcntl_signal(SIGTSTP, SIG_IGN);
    pcntl_signal(SIGTTOU, SIG_IGN);
    pcntl_signal(SIGTTIN, SIG_IGN);
    pcntl_signal(SIGHUP, SIG_IGN);
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

  private function toBackground()
  {
    // Fork a child and exit parent process: this is a daemon. Child continues.
    $pid = pcntl_fork();
    $this->pid = getmypid();
    switch( $pid )
    {
      case -1:
        // Error
        Log::error( "fork to background failed" );
        die();
      case 0:
        // Child
        break;
      default:
        // Parent
        exit;
    }
  }

  protected function run()
  {
    $timeToKill = -1;
    for(;;)
    {
      // Check up on children
      for( $i=0 ; $i<count($this->childPids) ; ++$i )
      {
        $pid = $this->childPids[$i];

        // Check child status
        $rv = pcntl_waitpid( $pid, $status, WNOHANG );
        if ( $rv == -1 )
        {
          // Child disappeared
          $ok = pcntl_wifexited( $status );
          if ( $ok )
          {
            // Normal exit
            Log::info( "child $pid exited" );
            $this->cleanup($pid);
          }
          else
          {
            // Unforeseen exit
            Log::error( "error with child $pid" );
            $this->cleanup($pid);
          }

          array_splice( $this->childPids, $i, 1 );
          if ( !$this->shutdown )
          {
            // Start a new child to keep the desired number intact
            $pid = $this->createChild();
            if ( $pid > 0 )
              $this->childPids[] = $pid;
          }

          // Child was removed from array, so decrement index
          --$i; continue;
        }
      }

      $count = count($this->childPids);
      if ( $count )
      {
        if ( $this->shutdown )
        {
          $newTimeToKill = $this->killTime - time();
          if ( $newTimeToKill > 0 && $newTimeToKill != $timeToKill )
          {
            // Wait for children to exit
            $timeToKill = $newTimeToKill;
            Log::info( "waiting $timeToKill seconds for $count children to exit" );
          }
          else if ( $newTimeToKill == 0 )
          {
            $this->reapChildren(true);
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

      // Explicitly give each child its own database instance, using the
      // same object gives headahes for forked processes.
      $this->db = new Database( Config::$db, true );
      $this->childInit();

      // Reset list of children, as a child we have none.
      if ( count($this->childPids) )
        $this->childPids = array();

      for( ;; )
      {
        $usleep = (int) $this->main();

        if ( $this->shutdown )
          exit();

        usleep($usleep);
      }
      exit();
    }
    return $pid;
  }
}

?>