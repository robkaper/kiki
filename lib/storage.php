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
  public static function findItemByName( $fileName )
  {
    $db = Core::getDb();

    $q = "SELECT id FROM storage WHERE original_name='%s'";
    $q = $db->buildQuery( $q, $fileName );
    $storageId = $db->getSingleValue( $q );

    if ( $storageId )
      return new StorageItem($storageId);

    return null;
  }

  public static function findItemByHash( $hash )
  {
    $db = Core::getDb();

    $q = "SELECT id FROM storage WHERE hash='%s'";
    $q = $db->buildQuery( $q, $hash );
    $storageId = $db->getSingleValue( $q );

    if ( $storageId )
      return new StorageItem($storageId);

    return null;
  }

  public static function findItemByNameSizeAndUser( $fileName, $size, $userId )
  {
    $db = Core::getDb();

    $q = "SELECT id FROM storage WHERE original_name='%s' AND size=%d AND user_id=%d";
    $q = $db->buildQuery( $q, $fileName, $size, $userId );
    $storageId = $db->getSingleValue( $q );

    if ( $storageId )
      return new StorageItem($storageId);

    return null;
  }

  public static function makeDirectory( $fileName )
  {
    $dirName = dirname( $fileName );
    if ( !file_exists($dirName) )
      mkdir($dirName, 0777, true);
    return $dirName;
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
   * Stores a resource.
   *
   * @param string $fileName original filename
   * @param string $data file data
   * @return int ID of the database entry created
   */
  public static function save( $fileName, $data, $size=0, $userId=null  )
  {
    $storageItem = new StorageItem();

    $fileName = strtolower($fileName);
    $fileName = preg_replace( '#(/)#', '', $fileName );

    $storageItem->setUserId( $userId );

    $storageItem->setOriginalName( $fileName );
    $storageItem->setData( $data );

    $storageItem->save();

    return $storageItem;
  }

  public static function delete( $id )
  {
    $storageItem = new StorageItem($id);
    if ( $storageItem->id() )
      $storageItem->delete();
  }

  private static function getThumbnailFileName( $fileName, $w, $h, $crop )
  {
    list( $base, $ext ) = StorageItem::splitExtension($fileName);
    $base = preg_replace( "#/storage/#", "/storage/thumbnails/", $base );
    $c = $crop ? "c." : null;

    return "${base}.${w}x${h}.${c}${ext}";
  }

  public static function getThumbnail( $fileName, $w, $h, $crop=false )
  {
    $scaledFile = self::getThumbnailFileName( $fileName, $w, $h, $crop );

    if ( !file_exists($scaledFile) )
      $scaledFile = self::generateThumbnail( $fileName, $w, $h, $crop );

    if ( !file_exists($scaledFile) )
      return null;

    return $scaledFile;
  }

  public static function generateThumbnail( $fileName, $w, $h, $crop )
  {
    $image = null;

    list( $base, $ext ) = StorageItem::splitExtension($fileName);
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

    if ( $w == 'auto' )
      $w = $dstW;
    if ( $h == 'auto' )
      $h = $dstH;

    $scaledFile = self::getThumbnailFileName( $fileName, $w, $h, $crop );
    if ( file_exists($scaledFile) )
      return $scaledFile;

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
    $hScale = (int) $newH ? $h / $newH : 1;
    $wScale = (int) $newW ? $w / $newW : 1;

    if ( $newW == 'auto' )
      $wScale = $hScale;

    if ( $newH == 'auto' )
      $hScale = $wScale;

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
