<?

// TODO: support choice of templates (/templates/coolblue/etc)

class Template
{
  static function file( $template )
  {
    // Try site specific template first
    $file = $GLOBALS['root']. '/templates/'. $template. '.tpl';
    if ( file_exists($file) )
      return $file;

    // Fallback to Kiki default template
    return $GLOBALS['kiki']. '/templates/'. $template. '.tpl';
  }
}

?>