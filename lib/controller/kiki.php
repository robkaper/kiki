<?php

namespace Kiki\Controller;

use Kiki\Controller;

use Kiki\Config;
use Kiki\Core;
use Kiki\Log;
use Kiki\Storage;
use Kiki\StorageItem;

class Kiki extends Controller
{
  public function actionHandler()
  {
    // if ( $this->actionHandler() )
    //   return;

    $this->fallback();
  }

  public function fallback()
  {
    $parts = parse_url($this->objectId);
    if ( !isset($parts['path']) )
      return false;

    $kikiFile = Core::getInstallPath(). "/htdocs/". $parts['path'];
    if ( file_exists($kikiFile) )
    {
      $ext = StorageItem::getExtension($kikiFile);
      switch($ext)
      {
        case 'css':
        case 'gif':
        case 'jpg':
        case 'js':
        case 'png':
        case 'webp':

          $this->altContentType = Storage::getMimeType($ext);
          $this->template = null;
          $this->status = 200;
          $this->content = file_get_contents($kikiFile);

           return true;
           break;

         case 'php':

           Log::debug( "PHP file $kikiFile" );

           $this->status = 200;
           $this->template = 'pages/default';

           $user = Core::getUser();
           $db = Core::getDb();

           include_once($kikiFile);

           return true;
           break;

         case '':

           if ( file_exists($kikiFile. "index.php") )
           {
             Log::debug( "PHP index file $kikiFile". "index.php" );

             $this->status = 200;
             $this->template = 'pages/default';

             $user = Core::getUser();
             $db = Core::getDb();

             include_once($kikiFile. "index.php");

             return true;
           }
           break;

        default:;

      }
      Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
     }
     else
    {
       Log::debug( "non-existing kikiFile $kikiFile" );
     }

    return false;
  }

  public function accountAction( $objectId )
  {
    $this->subController = new Account();
    $this->subController->setObjectId( $objectId );

    $result = $this->subController->actionHandler();
    if ( !$result )
      unset($this->subController);

    return $result;
  }
}
