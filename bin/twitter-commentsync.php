#!/usr/bin/php -q
<?
  $_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
  require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

  $q = "SELECT DISTINCT connection_id as id FROM publications p LEFT JOIN connections c ON c.external_id=p.connection_id WHERE c.service='User_Twitter'";
  $connectionIds = $db->getArray($q);

  $objectIds = array();
      
  foreach( $connectionIds as $connectionId )
  {
    $q = $db->buildQuery(
      "SELECT external_id, object_id FROM publications WHERE connection_id=%d",
      $connectionId );

    $rs = $db->query($q);
    while( $o = $db->fetchObject($rs) )
    {
      $objectIds[$o->external_id] = $o->object_id;
    }
    
    $apiUser = Factory_User::getInstance( 'User_Twitter', $connectionId );
    // $rs = $apiUser->api()->get('account/rate_limit_status');

    $getMore = true;
    $maxId = 0;
    while( $getMore )
    {
      $arrParams = array( 'count' => 800, 'include_rts' => true, 'replies' => 'all' );
      if ( $maxId )
        $arrParams['max_id'] = $maxId;

      $rs = $apiUser->api()->get('statuses/mentions', $arrParams );

      if ( count($rs) == 0 )
        $getMore = false;
      else
      {
        foreach( $rs as $mention )
        {
          if ( isset($objectIds[$mention->in_reply_to_status_id]) )
          {
            $twUser = Factory_User::getInstance( 'User_Twitter', $mention->user->id );
            $localUser = ObjectCache::getByType( 'User', $twUser->kikiUserId() );

            if ( !$twUser->id() )
            {
              $twUser->setName( $mention->user->name );
              $twUser->setScreenName( $mention->user->screen_name );
              $twUser->setPicture( $mention->user->profile_image_url );
              $twUser->link( $localUser->id() );
            }

            $ctime = strtotime($mention->created_at);
            $objectId = isset($objectIds[$mention->in_reply_to_status_id]) ? $objectIds[$mention->in_reply_to_status_id] : 0;
            $name = $twUser->name();

            $q = $db->buildQuery( "SELECT id FROM connections WHERE external_id=%d", $twUser->externalId() );
            $connectionId = $db->getSingleValue($q);

            // Find comment
            // FIXME: properly store externalId instead of checking timestamp
            $q = $db->buildQuery( "SELECT c.id from comments c LEFT JOIN objects o ON o.object_id=c.object_id WHERE ctime=from_unixtime(%d) and in_reply_to_id=%d and (user_id=%d or user_connection_id=%d)", $ctime, $objectId, $localUser->id(), $connectionId );
            $commentId = $db->getSingleValue( $q );
            if ( $commentId )
              continue;

            // Store comment
            $comment = new Comment();
            $comment->setInReplyToId( $objectId );
            $comment->setUserId( $localUser->id() );
            $comment->setConnectionId( $connectionId );
            $comment->setBody( $mention->text );

            $comment->setCtime( $ctime );
            $comment->save();
            
            print_r($comment);
          }
          $maxId = $mention->id-1;
        }
      }
    }
  }
