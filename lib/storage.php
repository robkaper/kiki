<?php

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

namespace Kiki;

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
    $uri = self::uri($id);

    $fileName = sprintf( "%s/storage/%s/%s/%s", Core::getRootPath(), $uri[0], $uri[1], $uri );

    // Move files directly under storage/ to better-scaling storage/0/f/ parallel directory structure
    $legacyFileName = sprintf( "%s/storage/%s", Core::getRootPath(), $uri );
    if ( !file_exists($fileName) && file_exists($legacyFileName) )
    {
      $dirName = self::makeDirectory($fileName);
      if ( file_exists($dirName) && is_dir($dirName) )
      {
        rename( $legacyFileName, $fileName);
        Log::debug( "moved $legacyFileName to $fileName" );
      }
    }

    return $fileName;
  }

  private static function makeDirectory( $fileName )
  {
    $dirName = dirname( $fileName );
    if ( !file_exists($dirName) )
      mkdir($dirName, 0777, true);
    return $dirName;
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
    $db = Core::getDb();

    $q = $db->buildQuery( "SELECT hash,extension FROM storage WHERE hash='%s'", $id );
    $o = $db->getSingleObject($q);
    if ( !$o )
    {
      $q = $db->buildQuery( "SELECT hash,extension FROM storage WHERE id=%d", $id );
      $o = $db->getSingleObject($q);
    }

    $extra = ($w && $h) ? ( ".${w}x${h}". ($crop ? ".c" : null) ) : null;
    return $o ? sprintf( "%s%s.%s", $o->hash, $extra, $o->extension ) : null;
  }

  public static function hash( $id )
  {
    $db = Core::getDb();

    $q = $db->buildQuery( "SELECT hash,extension FROM storage WHERE hash='%s'", $id );
    $o = $db->getSingleObject($q);
    if ( !$o )
    {
      $q = $db->buildQuery( "SELECT hash,extension FROM storage WHERE id=%d", $id );
      $o = $db->getSingleObject($q);
    }

    return $o->hash ?? null;
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
    list( , $ext ) = self::splitExtension($name);
    return $ext;
  }

  /**
   * Returns a mimetype based on a filename's extension.
   *
   * Supports only those mimetypes internally used by Kiki.
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
      case 'html':
	return 'text/html';
      case 'jpg':
        return 'image/jpeg';
      case 'js':
        return 'application/javascript';
      case 'png':
        return 'image/png';
      case 'webp':
        return 'image/webp';
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
  public static function url( $id, $w=0, $h=0, $crop=false, $secure = true )
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
  public static function save( $fileName, $data, $size=0 )
  {
    $db = Core::getDb();

    $fileName = strtolower($fileName);

    // FIXME: care about actual mimetypes, not extensions
    $fileName = preg_replace( '#(/)#', '', $fileName );
    $extension = self::getExtension( $fileName );

    $hash = sha1( uniqid(). $data );

    $qHash = $db->escape( $hash );
    $qName = $db->escape( $fileName );
    $qExt = $db->escape( $extension );
    $qSize = $db->escape( $size );

    $q = "insert into storage(hash, original_name, extension, size) values('$qHash', '$qName', '$qExt', $qSize)";
    Log::debug($q);
    $rs = $db->query($q);
    $id = $db->lastInsertId($rs);

    $localFile = self::localFile($id);

    self::makeDirectory($localFile);
    file_put_contents( $localFile, $data );
    chmod( $localFile, 0666 );

    return $id;    
  }

  public static function delete( $id )
  {
    $db = Core::getDb();

    $localFile = self::localFile($id);
    unlink($localFile);

    $q = "DELETE FROM storage WHERE id=%d";
    $q = $db->buildQuery( $q, $id );
    $db->query($q);
  }

  private static function getThumbnailFileName( $fileName, $w, $h, $crop )
  {
    list( $base, $ext ) = self::splitExtension($fileName);
    $base = preg_replace( "#/storage/#", "/storage/thumbnails/", $base );
    $c = $crop ? "c." : null;

    return "${base}.${w}x${h}.${c}${ext}";
  }

  public static function getThumbnail( $fileName, $w, $h, $crop=false )
  {
    $scaledFile = self::getThumbnailFileName( $fileName, $w, $h, $crop );

    if ( !file_exists($scaledFile) )
      self::generateThumbnail( $fileName, $w, $h, $crop );

    if ( !file_exists($scaledFile) )
      return null;

    return $scaledFile;
  }

  public static function generateThumbnail( $fileName, $w, $h, $crop )
  {
    $image = null;

    list( $base, $ext ) = self::splitExtension($fileName);
    $mimeType = mime_content_type($fileName);

    switch($mimeType)
    {
      case "image/gif":
        $image = \imagecreatefromgif($fileName);
        $ext = 'gif';
        break;

      case "image/jpeg":
        $image = \imagecreatefromjpeg($fileName);
        break;
        $ext = 'jpg';

      case "image/png":
        $image = \imagecreatefrompng($fileName);
        $ext = 'png';
        break;

      case "image/webp":
        $image = \imagecreatefromwebp($fileName);
        $ext = 'webp';
        break;

      default:;
    }
        
    if ( !$image )
      return false;

    $srcW = imagesx( $image );
    $srcH = imagesy( $image );
    $srcX = $srcY = $dstX = $dstY = 0;

    list( $dstW, $dstH, $scaleRatio ) = self::calculateScaleSize( $srcW, $srcH, $w, $h, !$crop );

    if ( $crop )
    {
      // Returned image is larger than dimensions, therefore we need to crop the the original image.
      if ( $dstW > $w )
        $dstX = ($w-$dstW)/2;
      else
        $dstY = ($h-$dstH)/2;
    }
    else
    {
      // Returned is smaller than dimensions, therefore we need to offset the target.
      if ( $dstW < $w )
        $dstX = ($w-$dstW)/2;
      else
        $dstY = ($h-$dstH)/2;
    }

    Log::debug( "resampling $fileName: w: $w, h: $h, dstX: $dstX, dstY: $dstY, srcX: $srcY, srcY: $srcY, dstW: $dstW, dstH: $dstH, srcW: $srcW, srcH: $srcH" );

    $scaled = imagecreatetruecolor( $w, $h );
    imagecopyresampled( $scaled, $image, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH );
    imageinterlace( $scaled, 1 );

    $scaledFile = self::getThumbnailFileName( $fileName, $w, $h, $crop );

    if ( file_exists($scaledFile) )
      chmod( $scaledFile, 0666 );
    else
      self::makeDirectory($scaledFile);

    switch($mimeType)
    {
      case "image/gif": 
        \imagegif( $scaled, $scaledFile );
        break;
      case "image/jpeg": 
        \imagejpeg( $scaled, $scaledFile, 95 );
        break;
      case "image/png": 
        \imagepng( $scaled, $scaledFile, 1 );
        break;
      case "image/webp":
        \imagewebp( $scaled, $scaledFile, 1 );
        break;
      default:;
    }

    chmod( $scaledFile, 0666 );
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
