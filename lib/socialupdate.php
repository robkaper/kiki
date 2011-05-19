<?

class SocialUpdate
{

  public static $fbRs = null;
  public static $twRs = null;

  public static function postStatus( &$user, $msg )
  {
    if ( !$msg )
      return;

    self::$fbRs = $user->fbUser->post( $msg );
    self::$twRs = $user->twUser->post( $msg );
  }

  public static function postPicture( &$user, $storageId, $title='', $description='' )
  {
    // Assumes a single picture posted outside the context of an album.
    // TODO: deprecate this, all pictures should end up in an album somehow, even if it's a default one.

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

  public static function postAlbumUpdate( &$user, &$album, &$pictures )
  {
    $count = count($pictures);
    $pictureId = end($pictures);

    // Post to Facebook

    $link = $album->url( $pictureId );
    
    $title = $album->title; // TODO: use title of picture if count==1
    $desc = ""; // TODO: use description of picture if count==1
    $caption = $_SERVER['SERVER_NAME'];

    $db = $GLOBALS['db'];
    $storageId = $db->getSingleValue( "select storage_id from pictures where id=$pictureId" );
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

  public static function postLink( &$user, $link )
  {
    // TODO: move from Article::save
    // TODO: add parameters, function assumes known details about link
    // TODO: support hashtags (manual but also pre-defined in article sections)
  }
}

?>
