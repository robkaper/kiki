<?php

class Controller_Kiki extends Controller
{
  public function exec()
  {
    $parts = parse_url($this->objectId);
    if ( !isset($parts['path']) )
      return false;

    $kikiFile = Kiki::getInstallPath(). "/htdocs/". $parts['path'];
    if ( file_exists($kikiFile) )
    {
      $ext = Storage::getExtension($kikiFile);
      switch($ext)
      {
        case 'css':
        case 'gif':
        case 'jpg':
        case 'js':
        case 'png':

          $this->altContentType = Storage::getMimeType($ext);
          $this->template = null;
          $this->status = 200;
          $this->content = file_get_contents($kikiFile);

          return;
          break;

        case 'php':

          Log::debug( "Controller_Kiki: PHP file $kikiFile" );

          $this->status = 200;
          $this->template = 'pages/default';

          $user = Kiki::getUser();
          $db = Kiki::getDb();

          include_once($kikiFile);

          return;
          break;

        case '':

          if ( file_exists($kikiFile. "index.php") )
          {
            Log::debug( "Controller_Kiki: PHP index file $kikiFile". "index.php" );

            $this->status = 200;
            $this->template = 'pages/default';

            $user = Kiki::getUser();
            $db = Kiki::getDb();

            include_once($kikiFile. "index.php");

            return;
          }
          break;

        default:;
      }
      Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
    }
    else
      Log::debug( "non-existing kikiFile $kikiFile" );
  }
}
  
?>