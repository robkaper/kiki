<?

/**
* @class Controller
* Controller class. Sort of.
* @todo Make this a proper abstract to be reimplemented, plus a factory so
* router can select the correct handler.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Controller
{
  public static function redirect( $url, $permanent = true )
  {
    Log::debug( "redirect($url)" );
    if ( !$url )
      return false;

    header( "Location: $url", true, $permanent ? 301 : 302 );
    return true;
  }

  public static function missingThumbnail( &$matches )
  {
    list( $dummy, $id, $w, $h, $dummy, $crop ) = $matches;

    if ( !$fileName = Storage::localFile($id) )
      return false;

    if ( !file_exists($fileName) )
      return false;

    $scaleFile = Storage::generateThumb( $fileName, $w, $h, $crop );
    if ( !file_exists($scaleFile) )
      return false;

    $ext = Storage::getExtension($scaleFile);
    switch( $ext )
    {
      case "gif":
      case "jpg":
      case "png":
        header( "Content-type: ". Storage::getMimeType($ext) );
        break;
      default:
        return false;
    }

    echo file_get_contents($scaleFile);
    return true;
  }

  public static function articles( $sectionId, $articleId )
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    if ( $articleId )
      $title = Articles::title( $db, $user, $articleId );
    else
      $title = Articles::sectionTitle( $db, $user, $sectionId );

    $page = new Page( $title );
    $page->addStylesheet( Config::$kikiPrefix. "/scripts/prettify/prettify.css" );
    $page->header();

    if ( $articleId )
    {
      if ( $title )
      {
        Log::debug("showSingle $articleId");
        echo Articles::showSingle( $db, $user, $articleId );
      }
      else
      {
        Log::debug("article404");
        // @todo allow setting custom 404 template
        return;
      }
    }
    else
    {
      Log::debug( "showMulti $sectionId" );
      echo Articles::showMulti( $db, $user, $sectionId, 2 );
    }

    $page->footer();
  }

}
  
?>