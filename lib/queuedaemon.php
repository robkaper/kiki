<?php

namespace Kiki;

class QueueDaemon extends Daemon
{
    private $queue = null;

    public function childInit()
    {
        parent::childInit();

        $this->queue = new ObjectQueue($this->db);
    }

    protected function cleanup($pid)
    {
        if ( $this->queue )
            $this->queue->cleanupPid($pid);
    }

    protected function main()
    {
        // Log::debug( "acquiring lock..." );
        $o = $this->queue->getNext( "lock_". $this->pid );
        if ( !$o )
        {
            // Log::debug( "nothing to do, long sleep..." );
            return 2000000;
        }

        // Might have been a while, reinit database connection
        if ( !$this->db || !$this->db->connected() || !$this->db->ping() )
            $this->db = Core::getDb(true);

        $actionHandler = $o->action. 'Action';

        if ( method_exists( $this, $actionHandler ) )
        {
            $retVal = $this->$actionHandler($o);
            if ( $retVal === true )
                $this->queue->markDone( $o->id );
            else if ( $retVal === false )
                $this->queue->markFailed( $o->id, $o->tries+1 );
            else
            {
                // TODO: neither marked done or failed, verify that queue time was set manually, otherwise mark as failed anyway
            }

            return 0;
        }

        Log::debug( "actionHandler $actionHandler not found, marking failed" );
        $this->queue->markFailed( $o->id, $o->tries+1 );
        return 1000000;
    }
}
