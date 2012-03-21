<?

/**
 * @class Storage
 *
 * Stores and retrieves local filesystem resources offering uniform database
 * and URI references.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

class Storage
{

  /**
   * Looks up the local filename of a stored resource.
   *
   * @param int $id ID of the database entry
   * @return string full path of the resource
   */
  public static function localFile( $id )
  {
    return sprintf( "%s/storage/%s", $GLOBALS['root'], self::uri($id) );
  }

  /**
   * Looks up the local URI a stored resource.
   *
   * URI uses a stored hash instead of the ID to prevent resources from
   * being discovered by simply incrementing the reference.
   *
   * @param int $id ID or hash of the database entry
   * @return string local URI of the resource
   */
  public static function uri( $id, $w=0, $h=0, $crop=false )
  {
    $db = $GLOBALS['db'];
    $qId = (int)$id;
    $qHash = $db->escape($id);
    $o = $db->getSingle( "select hash,extension from storage where id=$qId or hash='$qHash'" );

    $extra = ($w && $h) ? ( ".${w}x${h}". ($crop ? ".c" : null) ) : null;
    return $o ? sprintf( "%s%s.%s", $o->hash, $extra, $o->extension ) : null;
  }

  /**
   * Splits a filename into a base part and extension.
   *
   * @param string $name name of the file (should not contain a path)
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
   *
   * @param string $name name of the file (should not contain a path)
   * @return string base name of the file
   */
  public static function getBase( $name )
  {
    list( $base ) = self::splitExtension($name);
    return $base;
  }

  /**
   * Retrieve the extension part of a filename.
   *
   * @param string $name name of the file
   * @return string extension of the file
   */
  public static function getExtension( $name )
  {
    list( $base, $ext ) = self::splitExtension($name);
    return $ext;
  }

  /**
   * Returns a mimetype based on a filename's extension.
   *
   * @warning Supports only those mimetypes internally used by Kiki.
   *
   * @param string $ext extension
   * return string mimetype
   */
  public static function getMimeType( $ext )
  {
    $ext = strtolower($ext);
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
   *
   * @param int [int] database ID of the resource
   * @return string raw data of the resource
   */
  public static function data( $id )
  {
    return file_get_contents( self::localFile($id) );
  }

  /**
   * Returns the full URL for a stored resource.
   *
   * @param int $id database ID of the resource
   * @param boolean $secure Return a HTTPS resource instead of HTTP
   * @return string Full URL (protocol, host, local URI) of the resource
   */
  public static function url( $id, $w=0, $h=0, $crop=false, $secure = false )
  {
    return "http". ($secure ? "s" : null). "://". $_SERVER['SERVER_NAME']. "/storage/". self::uri($id,$w,$h,$crop);
  }

  /**
   * Stores a resource.
   *
   * @param string $fileName original filename
   * @param string $data file data
   * @return int ID of the database entry created
   */
  public static function save( $fileName, $data )
  {
    $db = $GLOBALS['db'];

    $fileName = strtolower($fileName);
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
   * Calculates dimensions for scaling an image.
   * 
   * Maintains aspect ratio. Returns the maximum size fitting within the
   * given dimensions, or a larger one in case pan and scan is true.
   *
   * @param int $w Original width.
   * @param int $h Original height.
   * @param int $newW Available width for the scaled image.
   * @param int $newH Available height for the scaled image.
   * @param boolean $panAndScan Whether the scaled image will be used in a pan and scan scenario, as opposed to letterbox.
   *
   * @return array Array containing the calculated width, height and scale factor.
   */
  public static function calculateScaleSize( $w, $h, $newW, $newH, $panAndScan=false )
  {
    $wScale = $w / $newW;
    $hScale = $h / $newH;

    if ( $panAndScan )
        $scaleFactor = max( $wScale, $hScale );
      else
        $scaleFactor = min( $wScale, $hScale );

    if ( $scaleFactor != 0 )
    {
      $w = $w / $scaleFactor;
      $h = $h / $scaleFactor;
    }

    return array( (int)$w, (int)$h, $scaleFactor );
  }

}

?>
