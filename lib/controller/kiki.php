<?

class Controller_Kiki extends Controller
{
  public function exec()
  {
    // @todo support DirectoryIndex equivalent, or else simply rely on
    // mod_rewrite and remove this
    $kikiFile = $GLOBALS['kiki']. "/htdocs/". $matches[1];
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
          Log::debug( "Controller_Kiki EXIT: PHP file $kikiFile" );
          include_once($kikiFile);
          exit();
          break;
        default:;
      }
      Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
    }
    else
      Log::debug( "non-existant kikiFile $kikiFile" );
  }
}
  
?>