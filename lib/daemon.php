<?php

namespace Kiki;

use Kiki\Core;
use Kiki\Log;

declare( ticks = 1 );

/**
 * Abstract class for daemon processes.
 *
 * @class Daemon
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

abstract class Daemon
{
  protected $pid = 0;

  protected $db = null;

  private $childPids = array();
  private $killPids = array();
  private $shutdown = false;
  private $killTime = 0;

  public function __construct()
  {
    $this->db = Core::getDb();
    
    $this->pid = getmypid();
  }

  protected function childInit()
  {
    // Reinit database connection... seems to avoid 'gone away' errors after forking...
    $this->db = Core::getDb(true);

    // Re-init log to create a unique ID.
    Log::init();
  }

  abstract protected function main();
  abstract protected function cleanup( $pid );
    
  public function start( $numChildren=3 )
  {
    $daemonMode = ( isset($_SERVER['SYSTEMD_EXEC_PID']) && !isset($_SERVER['STY']) );

    if ( $daemonMode )
      $this->closeFileHandles();

    $this->setHandlers();

    // Prior to forking children, otherwise they end up as orphans.
    if ( $daemonMode )
    {
      $this->toBackground();
      $this->detach();
    }

    Log::info( sprintf( "started, pid:%d, daemon-mode:%d", $this->pid, $daemonMode ) );

    for( $i=0 ; $i<$numChildren ; $i++ )
    {
      $pid = $this->createChild();
      if ( $pid > 0 )
        $this->childPids[]=$pid;
    }

    $this->run();

    Log::debug( "exiting" );
    exit(0);
  }

  private function closeFileHandles()
  {
    fclose(STDIN);
    fclose(STDOUT);
//    fclose(STDERR);
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
      if ( count($this->childPids) )
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
    $signalStr = $kill ? "SIGKILL" : "SIGTERM";
    for( $i=0 ; $i<count($this->childPids) ; ++$i )
    {
      $pid = $this->childPids[$i];

      Log::info( "sending $signalStr to child $pid" );
      $rv = posix_kill($pid, ($kill ? SIGKILL : SIGTERM) );

      if ( $kill )
        $this->killPids[] = $pid;
      if ( !$rv )
        Log::error( "signal $signalStr to child $pid failed" );
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
        exit(0);
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
        $status = null;
        $rv = pcntl_waitpid( $pid, $status, WNOHANG );
        if ( $rv == -1 )
        {
          // Child disappeared
          $ok = pcntl_wifexited($status);
          $exitstatus = pcntl_wexitstatus($status);

          if ( $ok )
          {
            // Normal exit
            Log::info( "child $pid exited" );
          }
          else
          {
            // Unforeseen exit
            Log::error( "error with child $pid" );
            // $this->cleanup($pid);
          }

          array_splice( $this->childPids, $i, 1 );
          if ( !$this->shutdown )
          {
            Log::debug( "creating a new child" );
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
            $this->reapChildren(true);
        }
        sleep(1);
      }
      else
      {
        for( $i=0 ; $i<count($this->killPids) ; ++$i )
        {
          $pid = $this->killPids[$i];
          // Log::info( "cleaning up for pid=[$pid]" );
          // $this->cleanup( $pid );
        }
        Log::info( "shutdown: no more children" );
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
      Log::info( "forked a child, pid $pid" );
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
        {
          $this->cleanup($this->pid);
          Log::info( "child shutting down, pid $this->pid" );
          exit(0);
        }

        usleep($usleep);
      }
      Log::error( "SNH: post-loop exit" );
      exit(0);
    }
    return $pid;
  }
}
