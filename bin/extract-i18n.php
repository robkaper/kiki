#!/usr/bin/php -q
<?
  $content = "<?php". PHP_EOL;

  array_shift($argv);
  foreach( $argv as $templateFile )
  {
    if ( !file_exists($templateFile) )
      continue;

    $text = file_get_contents($templateFile);

    $matches = array();
    $re = '~\{"([^"]+)"\|([^}]+)\}~';
    preg_match_all( $re, $text, $matches );
    if ( isset($matches[1]) )
    {
      foreach( $matches[1] as $phrase )
      {
        $content .= "_(\"$phrase\");". PHP_EOL;
      }
    }
  }
  
  echo $content;
