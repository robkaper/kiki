<?

/**
* @class Controller
* Controller class returning a content handler for URIs.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/

class Controller
{
  private $routes;
  
  public function addRoute( $uri, $type, $id=0 )
  {
    $routes["$uri"] = array( 'type' => $type, 'id' => $id );
  }

  public function getRoutes()
  {
    return $routes;
  }

  public function getHandler( $uri )
  {
    $matches = preg_match_all( $uri, 

  }

  public static function checkMissingThumbnail()
  {
    if ( !preg_match('#^/storage/([^\.]+)\.([^x]+)x([^\.]+)\.((c?)(m?))?#', $_SERVER['REQUEST_URI'], $matches) )
      return;

    list( $dummy, $id, $w, $h, $dummy, $crop, $maintainAspectRatio ) = $matches;
    if ( !$fileName = Storage::localFile($id) )
      return;

    if ( !file_exists($fileName) )
      return;

    $scaleFile = Storage::generateThumb( $fileName, $w, $h, $crop, $maintainAspectRatio );
    if ( !file_exists($scaleFile) )
      return;

    switch( Storage::getExtension($scaleFile) )
    {
      case "gif":
        header( "Content-type: image/gif" );
        break;
      case "jpg":
        header( "Content-type: image/jpeg" );
        break;
      case "png":
        header( "Content-type: image/png" );
        break;
      default:
        return;
    }

    echo file_get_contents($scaleFile);
    exit();
  }

}
  
?>