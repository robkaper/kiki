<?php

namespace Kiki\Controller\Kiki;

use Kiki\Controller\Kiki as KikiController;

use Kiki\Core;
use Kiki\Log;
use Kiki\ClassHelper;
use Kiki\User;

class Objects extends KikiController
{
  public function actionAction()
  {
    $db = Core::getDb();
    $user = Core::getUser();

    if ( !$user->id() )
      return false;

    $json = $objectId = $sction = $comment = null;
    if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
    {
      $json = file_get_contents('php://input');
      $data = json_decode($json);

      $json = $data->json ?? false;
      $objectId = $data->objectId ?? 0;
      $action = $data->action ?? null;
      $comment = $data->comment ?? null;
    }

    if ( !$json )
    {
      Log::debug( "not called as json" );
      return false;
    }

    $q = "SELECT `type` FROM `objects` WHERE `object_id`=%d";
    $objectType = $db->getSingleValue( $db->buildQuery( $q, $objectId ) );
    if ( !$objectType )
    {
      Log::debug( "no objectType for objectId: $objectId" );
      return false;
    }

    $objectClassName = ClassHelper::bareToNamespace( $objectType );
    $object = new $objectClassName( null, $objectId );
    if ( !$object->objectId() )
    {
      Log::debug( "object not found for type $objectType and objectId: $objectId" );
      return false;
    }

    if ( method_exists( $object, 'privacyLevel' ) )
    {
      $privacyLevel = $object->privacyLevel();

      Log::debug( "priv: $privacyLevel" );
      $objectUser = new User( $object->userId() );

      // TODO: checkPrivacyLevel is not part of Kiki yet. Should/could be part of BaseObject, not PrivacyController
      // $objectAvailable = $this->checkPrivacyLevel( $privacyLevel, $objectUser, true );
      $objectAvailable = true;

      Log::debug( "available: $objectAvailable" );
      if ( !$objectAvailable )
        return false;
    }

    $validAction = false;

    $likeCount = null;
    $commentCount = null;

    switch ( $action )
    {
      case 'comment':
        $validAction = true;

        $commentClass = ClassHelper::bareToNamespace( 'Comment' );

	$commentClass::insert( $object->objectId(), $user->id(), $comment );
        $commentCount = $commentClass::count( $object->objectId() );

        $status = true;

        $comment = $commentClass::html( $user, time(), $comment );

        if ( $commentCount == 1 )
        {
          $msg = sprintf( 'New comment from %s on %s',
            $user->objectName(),
            $object->objectName(),
          );
        }
        else
        {
          $msg = sprintf( 'New comment from %s on %s (%d total)',
            $user->objectName(),
            $object->objectName(),
            $commentCount,
          );
        }

        // TODO: custom extensions, must provide something in Kiki
        $notificationClass = ClassHelper::bareToNamespace( 'Notifications' );
        $notificationTypeClass = ClassHelper::bareToNamespace( 'NotificationType' );
        if ( $notificationClass && class_exists($notificationClass) && $notificationTypeClass && class_exists($notificationTypeClass) )
        {
          $notification = $notificationClass::exists( $object->userId(), $object->objectId(), $notificationTypeClass::Comments_New );

          if ( $notification )
            $notificationClass::update( $notification->id, $user->objectId(), $msg );
          else
            $notificationClass::insert( $object->userId(), $notificationTypeClass::Comments_New, $object->objectId(), $user->objectId(), $msg );
        }

        $badgeClass = ClassHelper::bareToNamespace( 'Badges' );
        if ( $badgeClass && class_exists($badgeClass) )
          $badgeClass::checkAccountSocial( $user->id() );

        break;

      case 'likes':
        $validAction = true;

        $q = "SELECT ctime FROM object_likes WHERE object_id = %d AND user_id = %d";
        $q = $db->buildQuery( $q, $object->objectId(), $user->id() );
        $ctime = $db->getSingleValue($q);

        if ( $ctime )
        {
          $q = "DELETE FROM object_likes WHERE object_id = %d AND user_id = %d";
          $q = $db->buildQuery( $q, $object->objectId(), $user->id() );
          $db->query($q);
          $status = false;

          $likes = $object->likes();
        }
        else
        {
          $q = "INSERT INTO object_likes (object_id, user_id) VALUES (%d, %d)";
          $q = $db->buildQuery( $q, $object->objectId(), $user->id() );
          $db->query($q);
          $status = true;

          $likes = $object->likes();

          // FIXME: allow overriding title for 'like' somewhere
          if ( $likes->count == 1 )
          {
            $msg = sprintf( 'New like from %s on %s',
              $user->objectName(),
              $object->objectName(),
            );
          }
          else
          {
            $msg = sprintf( 'New like from %s on %s (%d total)',
              $user->objectName(),
              $object->objectName(),
              $likes->count,
            );
          }

          // TODO: custom extensions, must provide something in Kiki
          $notificationClass = ClassHelper::bareToNamespace( 'Notifications' );
          $notificationTypeClass = ClassHelper::bareToNamespace( 'NotificationType' );
          if ( $notificationClass && class_exists($notificationClass) && $notificationTypeClass && class_exists($notificationTypeClass) )
          {
            $notification = $notificationClass::exists( $object->userId(), $object->objectId(), $notificationTypeClass::Props_New );

            if ( $notification )
              $notificationClass::update(  $notification->id, $user->objectId(), $msg );
            else
              $notificationClass::insert( $object->userId(), $notificationTypeClass::Props_New, $object->objectId(), $user->objectId(), $msg );
          }

          $badgeClass = ClassHelper::bareToNamespace( 'Badges' );
          if ( $badgeClass && class_exists($badgeClass) )
            $badgeClass::checkAccountSocial( $user->id() );
        }

        $likeCount = $likes->count;

        break;

      default:;
    }

    if ( !$validAction )
    {
      $this->status = 422;
      $this->template = null;
      $this->altContentType = null;
      $this->content = null;
      return false;
    }

    $this->status = 200;
    $this->template = null;
    $this->altContentType = 'application/json';

    $this->content = json_encode( [
      'objectId' => $object->objectId(),
      'action' => $action,
      'status' => $status,
      'likes' => $likeCount,
      'comments' => $commentCount,
      'comment' => $comment,
    ] );

    return true;
  }
}
