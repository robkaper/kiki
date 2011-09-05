<?

/**
* @file lib/storage.php
* Provides the Storage class.
* @class Storage
* Stores and retrieves local filesystem data offering database and URI references.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/ 

class Storage
{

  /**
  * Looks up the local filename of a stored resource.
  * @param $id [int] ID of the database entry
  * @return string full path of the resource
  */
  public static function localFile( $id )
  {
    return sprintf( "%s/storage/%s", $GLOBALS['root'], self::uri($id) );
  }

  /**
  * Looks up the local URI a stored resource.
  * @param $id [int] ID or hash of the database entry
  * @return string local URI of the resource
  */
  public static function uri( $id )
  {
    $db = $GLOBALS['db'];
    $qId = (int)$id;
    $qHash = $db->escape($id);
    $o = $db->getSingle( "select hash,extension from storage where id=$qId or hash='$qHash'" );
    return $o ? sprintf( "%s.%s", $o->hash, $o->extension ) : null;
  }

  /**
  * Splits a filename into a base part and extension.
  * @param $name [string] name of the file (should not contain a path)
  * @return array base name and extension of the file
  */
  public static function splitExtension( $name )
  {
    $pos = strrpos( $name, '.' );
    if ( $pos === FALSE )
      return array( $name, null );

    $base = substr( $name, 0, $pos );
    $ext = substr( $name, $pos+1 );
    return array( $base, $ext );
  }

  /**
  * Retrieve the base part of a filename.
  * @param $name [string] name of the file (should not contain a path)
  * @return string base name of the file
  */
  public static function getBase( $name )
  {
    list( $base ) = self::splitExtension($name);
    return $base;
  }

  /**
  * Retrieve the extension part of a filename.
  * @param $name [string] name of the file
  * @return string extension of the file
  */
  public static function getExtension( $name )
  {
    list( $base, $ext ) = self::splitExtension($name);
    return $ext;
  }
  
  /**
  * Retrieves the raw data of a stored resource.
  * @param $id [int] database ID of the resource
  * @return string raw data of the resource
  */
  public static function data( $id )
  {
    return file_get_contents( self::localFile($id) );
  }

  /**
  * Generates a URL for a stored resource.
  * @param $id [int] database ID of the resource
  * @return string Full URL (protocol, host, local URI) of the resource
  */
  public static function url( $id )
  {
    return "http://". $_SERVER['SERVER_NAME']. "/storage/". self::uri($id);
  }

  /**
  * Stores a resource.
  * @param $fileName [string] original filename
  * @param $data [string] file data
  * @return int ID of the database entry created
  */
  public static function save( $fileName, $data )
  {
    $db = $GLOBALS['db'];

    $extension = self::getExtension( $fileName );
    $hash = sha1( uniqid(). $data );

    $qHash = $db->escape( $hash );
    $qName = $db->escape( $fileName );
    $qExt = $db->escape( $extension );
    $qSize = $db->escape( sizeof($data) );

    $q = "insert into storage(hash, original_name, extension, size) values('$qHash', '$qName', '$qExt', $qSize)";
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);

    $fileName = self::localFile($id);
    file_put_contents( $fileName, $data );
    chmod( $fileName, 0644 );

    return $id;    
  }

  public static function generateThumb( $fileName, $w, $h, $crop=false, $maintainAspectRatio=true )
  {
    list( $base, $ext ) = self::splitExtension($fileName);
    switch($ext)
    {
      case "gif":
        $image = imagecreatefromgif($fileName);
        break;
      case "jpg":
        $image = imagecreatefromjpeg($fileName);
        break;
      case "png":
        $image = imagecreatefrompng($fileName);
        break;
      default:;
    }
        
    if ( !$image )
      return false;

    $srcW = imagesx( $image );
    $srcH = imagesy( $image );
    $srcX = $srcY = $dstX = $dstY = 0;

    if ( $maintainAspectRatio )
    {
      if ( $crop )
      {
        // Maintaining aspect ratio with crop, returned dimensions are larger than actual image
        list( $newW, $newH, $scaleRatio ) = self::calculateScaleSize( $srcW, $srcH, $w, $h, false );

        // Therefore, we offset the original image so the thumbnail gets the content from the center
        if ( $scaleRatio<1 )
        {
          $srcY = ((1-$scaleRatio)*$srcH)/2;
          $srcH -= $srcY;
        }
        else
        {
          $srcX = (($scaleRatio-1)*$srcW)/2;
          $srcW -= $srcX;
        }
      }
      else
      {
        // Maintaining aspect ratio without crop, returned dimensions are smaller than actual image
        list( $newW, $newH, $scaleRatio ) = self::calculateScaleSize( $srcW, $srcH, $w, $h );

        // Therefore, we offset the destination so the content gets centered in the thumbnail
        if ( $scaleRatio>1 )
          $dstY = ($h-$newH)/2;
        else
          $dstX = ($w-$newW)/2;
      }
    }
    else
    {
      // scale indiscriminately, black bars will occur
      list( $newW, $newH ) = array( $w, $h );
    }

    Log::debug( "resampling $fileName: dstX: $dstX, dstY: $dstY, srcX: $srcY, srcY: $srcY, dstW: $dstW, dstH: $dstH, srcW: $srcW, srcH: $srcH" );
    $scaled = imagecreatetruecolor( $w, $h );
    imagecopyresampled( $scaled, $image, $dstX, $dstY, $srcX, $srcY, $newW, $newH, $srcW, $srcH );
    imageinterlace( $scaled, 1 );

    $c = $crop ? "c" : null;
    $m = $maintainAspectRatio ? "m" : null;
    $cmdot = ($c||$m) ? ".": null;
    $scaledFile = "${base}.${w}x${h}.${c}${m}${cmdot}${ext}";
    Log::debug( "resampled file is $scaledFile" );

    if ( file_exists($scaledFile) )
      chmod( $scaledFile, 0664 );

    switch($ext)
    {
      case "gif": 
        imagegif( $scaled, $scaledFile );
        break;
      case "jpg": 
        imagejpeg( $scaled, $scaledFile, 95 );
        break;
      case "png": 
        imagepng( $scaled, $scaledFile, 1 );
        break;
      default:;
    }

    chmod( $scaledFile, 0664 );
    return $scaledFile;
  }

  /**
   * In case both newW and newH are given and the resulting ratios differ,
   * the returned dimension is the smallest fitting.  Set $useMaxRatio to
   * false to go out of bounds (to allow an image to be cropped later).
   */
  public static function calculateScaleSize( $w, $h, $newW=0, $newH=0, $useMaxRatio=true )
  {
    $wScale = $w / $newW;
    $hScale = $h / $newH;

    if ( $newW && $newH )
    {
      if ( $useMaxRatio )
        $ratio = max( $wScale, $hScale );
      else
        $ratio = min( $wScale, $hScale );
    }
    else if ( $newW )
      $ratio = $wScale;
    else if ( $newH )
      $ratio = $hScale;
    else
      $ratio = 1;

    if ( $ratio != 0 )
    {
      $w = $w / $ratio;
      $h = $h / $ratio;
    }

    return array( (int)$w, (int)$h, $wScale/$hScale );
  }

}

?>
