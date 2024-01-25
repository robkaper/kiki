<?php

/**
 * Class providing the Comment object.
 *
 * Comments are responses attached to base objects.
 *
 * They used to be a base object themselves allowing replies, but this is currently deprecated in favour of flat-comment lists.
 *
 * @package Kiki
 * @author Rob Kaper <https://robkaper.nl/>
 * @copyright 2024 Rob Kaper <https://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki;

class Comment
{
  // Returns a HTML representation of the comment.
  // This is only used for adding a new comment after the JSON call in Kiki\Controller\Kiki\Objects.
  // FIXME: deprecate this and let the template construct the HTML.
  public static function html( $cUser, $ctime, $comment )
  {
    $date = Misc::relativeTime($ctime). " ago";

    return sprintf( '<a href="%s">%s</a> &mdash; <span class="smaller">%s</span><blockquote>%s</blockquote><hr class="cl grey">'. PHP_EOL,
      $cUser->url(),
      htmlspecialchars( $cUser->name() ),
      $date,
      htmlspecialchars($comment)
    );
  }

  public static function insert( $objectId, $userId, $comment )
  {
    $db = Core::getDb();

    $q = "INSERT INTO object_comments (object_id, user_id, comment) VALUES (%d, %d, '%s')";
    $db->query( $db->buildQuery( $q, $objectId, $userId, $comment ) );
  }

  public static function count( $objectId )
  {
    $db = Core::getDb();

    $q = "SELECT COUNT(*) FROM object_comments WHERE object_id=%d";
    return $db->getSingleValue( $db->buildQuery( $q, $objectId ) );
  }

  public static function get( $objectId )
  {
    $db = Core::getDb();

    $q = "SELECT ctime, user_id, comment FROM object_comments WHERE object_id = %d ORDER BY ctime ASC";
    return $db->getObjects( $db->buildQuery( $q, $objectId ) );
  }
}
