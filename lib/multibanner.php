<?php

class MultiBanner
{
  public static function articles( $sectionId )
  {
    $db = $GLOBALS['db'];
    $content = null;

    $articles = array();
    $q = $db->buildQuery( "select id from articles where header_image!=0 and featured=true and visible=true and ctime<=now() and section_id=%d order by ctime desc limit 4", $sectionId );
    $rs = $db->query($q);
    if ( $rs && $count = $db->numRows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $article = new Article( $o->id );
        $articles[] = $article;
      }
    }
    else
      return $content;

    $widths = array();
    $widths[1] = array( 780 );
    $widths[2] = array( 520, 260 );
    $widths[3] = array( 390, 195, 195 );
    $widths[4] = array( 195, 195, 195, 195 );

    list($articles) = array_chunk( $articles, 4 );
    $count = count($articles);
    if ( !$count )
      return null;

    $content .= "<div class=\"banner\">\n";
    foreach( $articles as $id => $article )
    {
      $width = $widths[$count][$id];

      $img = Storage::url( $article->headerImage() );
      list ($base, $ext) = Storage::splitExtension( $img );
      $img = "${base}.${width}x250.c.${ext}";

      $content .= "<div class=\"bannerItem\" style=\"width: ${width}px; background-image: url($img);\">\n";
      $content .= "<a href=\"". $article->url(). "\">". $article->title(). "</a>";
      $content .= "</div>\n"; 
    }

    $content .= "<br class=\"spacer\">";
    $content .= "</div>\n";
    
    return $content;
  }
}

?>
