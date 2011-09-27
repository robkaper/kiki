<?

/**
 * Utility class to post updates to social networks.
 *
 * @deprecated Not currently in use by other Kiki internal classes, will be removed shortly or refactored to some sort of generic publication class.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo Allow selection of which networks to post to, currently this class
 * posts to all networks available.
 */

class SocialUpdate
{
  public static $type = null;
  public static $fbRs = null;
  public static $twRs = null;

  /**
  * Posts a simple status update.
  * @param $user [User] user to post as
  * @param $msg [string] message to post
  */
  public static function postStatus( &$user, $msg )
  {
    if ( !$msg )
      return;

    self::$type = 'status';
    self::$fbRs = $user->fbUser->post( $msg );
    self::$twRs = $user->twUser->post( $msg );
  }

  /**
  * Posts a single picture (link).
  * @param $user [User] user to post as
  * @param $storageId [int] database ID of the picture's Storage entry
  * @param $title [string] title/caption of the picture
  * @param $description [string] description of the picture
  * @todo Pictures in Storage should all end up in an album view somehow, so
  *   deprecate that use and convert this method to one that accepts URLs.
  */
  public static function postPicture( &$user, $storageId, $title='', $description='' )
  {
    $link = Storage::url( $storageId );

    // Post to Facebook

    $fbTitle = $title ? $title : "Untitled picture";
    $caption = $_SERVER['SERVER_NAME'];
    $picture = Storage::url( $storageId );

    $fbMsg = 'uploaded a picture:';
    self::$fbRs = $user->fbUser->post( $fbMsg, $link, $fbTitle, $caption, $description, $picture );

    // Post to Twitter

    $tinyUrl = TinyUrl::get( $link );

    if ( $title )
    {
      $prefix = "uploaded a picture: ";
      $msg = $title;
      $postfix = " $tinyUrl";
    }
    else if ( $description )
    {
      $prefix = '';
      $msg = $description;
      $postfix = " $tinyUrl";
    }
    else
    {
      $prefix = "uploaded a picture:";
      $msg = '';
      $postfix = " $tinyUrl";
    }

    $maxLength = 140 - strlen($prefix) - strlen($postfix);
    $msg = Misc::textSummary( $msg, $maxLength );
    $twMsg = "${prefix}${msg}${postfix}";
    self::$twRs = $user->twUser->post( $twMsg );
  }

  /**
  * Posts an album update.
  * @param $user [User] user to post as
  * @param $album [Album] the album that was updated
  * @param $pictures [array] the pictures included in this update
  * @return string album URL of the last picture included in this update
  */
  public static function postAlbumUpdate( &$user, &$album, &$pictures )
  {
    self::$type = 'album';

    $count = count($pictures);
    $lastPicture = end($pictures);
    reset($pictures);

    // Post to Facebook
    $link = $album->url( $lastPicture['id'] );
    if ( $count==1 )
    {
      $title = $lastPicture['title'];
      $desc = $lastPicture['description'];
    }
    else
    {
      $title = $album->title;
      $desc = "";
      foreach( $pictures as $picture )
        $desc .= $picture['title']. "\n";
    }

    $caption = $_SERVER['SERVER_NAME'];

    $db = $GLOBALS['db'];
    $storageId = $db->getSingleValue( "select storage_id from pictures where id=". $lastPicture['id'] );
    $picture = Storage::url( $storageId );

    $fbMsg = "uploaded ". ($count==1 ? "a picture" : "$count pictures"). " to album '$album->title':";
    self::$fbRs = $user->fbUser->post( $fbMsg, $link, $title, $caption, $desc, $picture );

    // Post to Twitter
    $tinyUrl = TinyUrl::get( $link );

    // TODO: add title/description of picture when count==1
    $prefix = "uploaded ". ($count==1 ? "a picture" : "$count pictures"). " to album '$album->title':";
    $msg = '';
    $postfix = " $tinyUrl";

    $maxLength = 140 - strlen($prefix) - strlen($postfix);
    $msg = Misc::textSummary( $msg, $maxLength );

    $twMsg = "${prefix}${msg}${postfix}";
    self::$twRs = $user->twUser->post( $twMsg );

    return $link;
  }

  /**
  * Posts a link/URL.
  * @param $user [User] user to post as
  * @param $link URL of the link to be posted
  * @warning not implemented yet
  * @todo move from Article::save()
  * @todo add parameters with link title/description details
  * @todo support hashtags (manual but also pre-defined in article sections)
  */
  public static function postLink( &$user, $link )
  {
  }
}

?>
