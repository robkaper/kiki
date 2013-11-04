<?php

/**
 * Class providing the Comment object.
 *
 * Comments are responses attached to base objects (but are also a base
 * object themselves, as comments can be in reply to each other)
 *
 * If any, comments store the user connection creating them, but also - if
 * any - the local user linked at the time of creation, which should
 * eventually be more authorative.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2012 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

use Kiki\Object;

class Comment extends Object
{
  private $ipAddr = '0.0.0.0';

  private $inReplyToId = null;

  private $connectionId = 0;
  private $externalId = null;

  private $body = null;

  public function reset()
  {
    parent::reset();

    $this->ipAddr = '0.0.0.0';

    $this->inReplyToId = 0;

    $this->connectionId = 0;
    $this->externalId = null;
    
    $this->body = null;
  }

  public function load()
  {
    $q = $this->db->buildQuery(
      "SELECT c.id, c.object_id, c.ip_addr, c.in_reply_to_id, o.user_id, c.user_connection_id, c.external_id, c.body, o.ctime, o.mtime FROM comments c LEFT JOIN objects o ON o.object_id=c.object_id WHERE c.id=%d OR o.object_id=%d",
      $this->id, $this->objectId
    );

    $o = $this->db->getSingleObject($q);
    $this->setFromObject( $o );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

    if ( !$o )
      return;

    $this->ipAddr = $o->ip_addr;

    $this->inReplyToId = $o->in_reply_to_id;

    $this->connectionId = $o->user_connection_id;
    $this->externalId = $o->external_id;
    
    $this->body = $o->body;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();

    $q = $this->db->buildQuery(
      "UPDATE comments SET object_id=%d, ip_addr='%s', in_reply_to_id=%d, user_connection_id=%d, external_id='%s', body='%s' WHERE id=%d",
      $this->objectId, $this->ipAddr, $this->inReplyToId, $this->connectionId, $this->externalId, $this->body, $this->id
    );

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    $q = $this->db->buildQuery(
      "INSERT INTO comments (object_id, ip_addr, in_reply_to_id, user_connection_id, external_id, body) VALUES (%d, '%s', %d, %d, '%s', '%s')",
      $this->objectId, $this->ipAddr, $this->inReplyToId, $this->connectionId, $this->externalId, $this->body
    );

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function url()
  {
    return null;
  }

  public function setIpAddr( $ipAddr ) { $this->ipAddr = $ipAddr; }
  public function ipAddr() { return $this->ipAddr; }

  public function setInReplyToId( $inReplyToId ) { $this->inReplyToId = $inReplyToId; }
  public function inReplyToId() { return $this->inReplyToId; }

  public function setConnectionId( $connectionId ) { $this->connectionId = $connectionId; }
  public function connectionId() { return $this->connectionId; }
  public function setExternalId( $externalId ) { $this->externalId = $externalId; }
  public function externalId() { return $this->externalId; }

  public function setBody( $body ) { $this->body = $body; }
  public function body() { return $this->body; }
}
