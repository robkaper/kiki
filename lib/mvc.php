<?

class MVC
{
  public function __construct()
  {
  }

  // Simple includer, tries document root first and falls back to Kiki root
  public function load( $section )
  {
    $local = $GLOBALS['root']. "/mvc/". strtolower( $section ). ".php";
    if ( file_exists($local) )
    {
      include "$local";
      return;
    }

    $kiki = $GLOBALS['root']. "/mvc/". strtolower( $section ). ".php";
    if ( file_exists($kiki) )
    {
      include "$kiki";
    }
  }

  // TODO: render HTML templates by subsitution of variables (will deprecate load method)
  // And of course implement if/else, loops, etc... might as well stick with good old include and not write our own language
  public function render()
  {
    return null;
  }
}

?>