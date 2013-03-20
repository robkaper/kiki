<?php

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

    $this->altContentType = Storage::getMimeType( Storage::getExtension($scaleFile) );
    $this->template = null; // Send content directly, without a template.
    $this->status = 200;
    $this->content = file_get_contents($scaleFile);
  }
}

?>
