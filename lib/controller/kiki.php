<?

class Controller_Kiki extends Controller
{
  public function exec()
  {
    // TODO: support DirectoryIndex equivalent, or else simply rely on
    // mod_rewrite and remove this
    $parts = parse_url($this->objectId);
    if ( !isset($parts['path']) )
      return false;

    $kikiFile = $GLOBALS['kiki']. "/htdocs/". $parts['path'];
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
          Log::debug( "Controller_Kiki EXIT: static file $kikiFile" );
          header('Content-Type: '. Storage::getMimeType($ext) );
          exit( file_get_contents($kikiFile) );
          break;
        case 'php':
          $user = $GLOBALS['user'];
          $db = $GLOBALS['db'];
          Log::debug( "Controller_Kiki EXIT: PHP file $kikiFile" );
          include_once($kikiFile);
          exit();
          break;
        case '':
          if ( file_exists($kikiFile. "index.php") )
          {
            $user = $GLOBALS['user'];
            $db = $GLOBALS['db'];
            Log::debug( "Controller_Kiki EXIT: PHP file $kikiFile". "index.php" );
            include_once($kikiFile. "index.php");
            exit();
          }
        default:;
      }
      Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
    }
    else
      Log::debug( "non-existant kikiFile $kikiFile" );
  }
}
  
?>