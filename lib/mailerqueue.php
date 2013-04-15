<?php

/**
 * Class to manage the e-mail queue.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo Add sent_time for tracking deliveries?
 */

class MailerQueue
{
  private $db;

  public function __construct( &$db )
  {
    $this->db = $db;
  }

  public static function store( &$email, $priority=10 )
  {
    $db = Kiki::getDb();

    $q = $db->buildQuery( "insert into mail_queue (msg_id, ctime, mtime, lock_id, priority, sent, subject, `from`, `to`, headers, body) values ('%s', now(), now(), null, %d, false, '%s', '%s', '%s', '%s', '%s')", $email->msgId(), $priority, $email->subject(), $email->from(), $email->to(), $email->headers(), $email->body() );
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);
    return $id;
  }

  /**
   * Retrieves the highest priority unsent oldest e-mail from the queue and
   * locks it.
   *
   * @param string $lockId lock ID
   * @return object database object
   */
  public function getNext( $lockId )
  {
    $q = "update mail_queue set lock_id='$lockId' where sent=false and lock_id is null order by priority desc, ctime asc limit 1";
    $rs = $this->db->query($q);

    $q = "select id,subject,`from`,`to`,headers,body from mail_queue where sent=false and lock_id='$lockId' order by priority desc, ctime asc limit 1";
    $o = $this->db->getSingle($q);

    // TODO: Email::setFromObject($o);
    if ($o)
      self::lock($o->id,$lockId);

    return $o;
  }

  /**
   * Locks a queued e-mail.
   *
   * @param int $id database ID of the queued e-mail
   * @param string $lock ID lock ID
   */
  private function lock( $id, $lockId )
  {
    $q = $this->db->buildQuery( "update mail_queue set lock_id='%s' where id=%d and lock_id is null", $lockId, $id );
    $rs = $this->db->query($q);
    
    if ( !$this->db->affectedRows($rs) )
    {
      // TODO: Error handing for no rows affected: either the mail was not
      // found, or it already had a lock.
    }
  }

  /**
   * Unlocks a queued e-mail.
   *
   * @param int $id database ID of the queued e-mail
   */
  private function unlock( $id )
  {
    $q = $this->db->buildQuery( "update mail_queue set lock_id=null where id=%d and lock_id is not null", $id );
    $rs = $this->db->query($q);

    if ( !$this->db->affectedRows($rs) )
    {
      // TODO: Error handing for no rows affected: either the mail was not
      // found, or it didn't have a lock.
    }
  }  

  /**
   * Marks an e-mail as sent. Also unlocks the e-mail.
   * @param int $id database ID of the queued e-mail
   */
  public function markSent( $id )
  {
    $q = $this->db->buildQuery( "update mail_queue set sent=true where id=%d", $id );
    $this->db->query($q);

    self::unlock($id);
  }
}
