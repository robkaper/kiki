<?php

namespace Kiki\Controller;

use Kiki\Log;

class Thumbnails extends \Kiki\Controller
{
  public function exec()
  {
    list( $dummy, $id, $w, $h, $dummy, $crop ) = $this->objectId;

    $this->objectId = $id;

    if ( !$fileName = \Kiki\Storage::localFile($this->objectId) )
      return false;

    if ( !file_exists($fileName) )
      return false;

    $scaleFile = \Kiki\Storage::generateThumb( $fileName, $w, $h, $crop );
    if ( !file_exists($scaleFile) )
      return false;

    // FIXME: duplicate from nginx config... how to keep this in sync?
    header( 'Expires: '. gmdate( 'D, d M Y H:i:s \G\M\T', time() + (60*60*24*365) ) ); // 365d

    $this->altContentType = \Kiki\Storage::getMimeType( \Kiki\Storage::getExtension($scaleFile) );
    $this->template = null; // Send content directly, without a template.
    $this->status = 200;
    $this->content = file_get_contents($scaleFile);
    
    return true;
  }
}

?>
