<?php

namespace Kiki\Controller;

class Thumbnails extends \Kiki\Controller
{
  public function exec()
  {
    list( $dummy, $id, $w, $h, $dummy, $crop ) = $this->objectId;

    if ( !$fileName = \Kiki\Storage::localFile($id) )
      return;

    if ( !file_exists($fileName) )
      return;

    $scaleFile = \Kiki\Storage::generateThumb( $fileName, $w, $h, $crop );
    if ( !file_exists($scaleFile) )
      return;

    $this->altContentType = \Kiki\Storage::getMimeType( \Kiki\Storage::getExtension($scaleFile) );
    $this->template = null; // Send content directly, without a template.
    $this->status = 200;
    $this->content = file_get_contents($scaleFile);
  }
}

?>
