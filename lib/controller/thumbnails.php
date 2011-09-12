<?

class Controller_Thumbnails extends Controller
{
  public function exec()
  {
    list( $dummy, $id, $w, $h, $dummy, $crop ) = $this->objectId;

    if ( !$fileName = Storage::localFile($id) )
      return;

    if ( !file_exists($fileName) )
      return;

    $scaleFile = Storage::generateThumb( $fileName, $w, $h, $crop );
    if ( !file_exists($scaleFile) )
      return;

    $ext = Storage::getExtension($scaleFile);
    switch( $ext )
    {
      case "gif":
      case "jpg":
      case "png":
        header( "Content-type: ". Storage::getMimeType($ext) );
        break;
      default:
        return;
    }

    $this->status = 200;
    $this->content = file_get_contents($scaleFile);
  }
}

?>
