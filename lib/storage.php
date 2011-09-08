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
  * Returns a mimetype based on a filename's extension.
  * @param $ext [string] extension
  * return string mimetype
  */
  public static function getMimeType( $ext )
  {
    switch($ext)
    {
      case 'css':
        return 'text/css';
      case 'gif':
        return 'image/gif';
      case 'jpg':
        return 'image/jpeg';
      case 'js':
        return 'application/javascript';
      case 'png':
        return 'image/png';
      default:;
    }

    return null;
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

  public static function generateThumb( $fileName, $w, $h, $crop=false )
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

    list( $dstW, $dstH, $scaleRatio ) = self::calculateScaleSize( $srcW, $srcH, $w, $h, !$crop );

    if ( $scaleRatio !=1 )
    {
      if ( $crop )
      {
        // Returned image is larger than dimensions, therefore we need to crop the the original image.
        if ( $dstW > $w )
        {
          $dstX = ($w-$dstW)/2;
        }
        else
        {
          $dstY = ($h-$dstH)/2;
        }
      }
      else
      {
        // Returned is smaller than dimensions, therefore we need to offset the target.
        if ( $dstW < $w )
          $dstX = ($w-$dstW)/2;
        else
          $dstY = ($h-$dstH)/2;
      }
    }

    Log::debug( "resampling $fileName: dstX: $dstX, dstY: $dstY, srcX: $srcY, srcY: $srcY, dstW: $dstW, dstH: $dstH, srcW: $srcW, srcH: $srcH" );
    $scaled = imagecreatetruecolor( $w, $h );
    imagecopyresampled( $scaled, $image, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH );
    imageinterlace( $scaled, 1 );

    $c = $crop ? "c." : null;
    $scaledFile = "${base}.${w}x${h}.${c}${ext}";
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
   * Calculates the dimensions of a scaled image, maintaining aspect ration. 
   * Returns the maximum size fitting within the given dimensions, or a
   * larger one in case pan and scan is true.
   */
  public static function calculateScaleSize( $w, $h, $newW, $newH, $panAndScan=false )
  {
    $wScale = $w / $newW;
    $hScale = $h / $newH;

    if ( $panAndScan )
        $ratio = max( $wScale, $hScale );
      else
        $ratio = min( $wScale, $hScale );

    if ( $ratio != 0 )
    {
      $w = $w / $ratio;
      $h = $h / $ratio;
    }

    return array( (int)$w, (int)$h, $ratio );
  }

}

?>
