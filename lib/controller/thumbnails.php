<?php

namespace Kiki\Controller;

use Kiki\Log;
use Kiki\Storage;
use Kiki\StorageItem;

class Thumbnails extends \Kiki\Controller
{
  public function exec()
  {
    list( $dummy, $id, $w, $h, $dummy, $crop ) = $this->objectId;

    $this->objectId = $id;

    $storageItem = Storage::findItemByHash( $this->objectId );

    if ( !$storageItem )
      return false;

    if ( !$fileName = $storageItem->localFile() )
      return false;

    if ( !file_exists($fileName) )
      return false;

    $scaleFile = Storage::getThumbnail( $fileName, $w, $h, $crop );
    if ( !file_exists($scaleFile) )
      return false;

    // FIXME: duplicate from nginx config... how to keep this in sync?
    header( 'Expires: '. gmdate( 'D, d M Y H:i:s \G\M\T', time() + (60*60*24*365) ) ); // 365d

    $this->altContentType = Storage::getMimeType( StorageItem::getExtension($scaleFile) );
    $this->template = null; // Send content directly, without a template.
    $this->status = 200;
    $this->content = file_get_contents($scaleFile);
    
    return true;
  }
}

?>
