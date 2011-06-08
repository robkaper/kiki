<?

// Contains everything that (as of yet) doesn't really fit into Kiki's object model.

class Misc
{
  // Returns a time description relative to the current time
  public static function relativeTime( $time )
  {
    $now = time();
    $time = strtotime( $time );
    $delta = $now - $time;

    if ( $delta < 60 )
      return "minder dan een minuut";
    else if ( $delta < 120 )
      return "een minuut";
    else if ( $delta < (4*60) )
      return "een paar minuten";
    else if ( $delta < (60*60) )
      return (int)($delta/60). " minuten";
    else if ( $delta < (120*60) )
      return "een uur";
    else if ( $delta < (24*60*60) )
      return (int)($delta/3600). " uur";
    else if ( $delta < (48*60*60) )
      return "een dag";
    else
      return (int)($delta/86400). " dagen";
  }

  // Turns text (plain or with BBcode markup) into HTML
  public static function markup( $text, $authorMode = true, $fullURLs = false )
  {
    // Turn ordinary URLs into [url]
    $text = preg_replace( "((\s)(http(s)?\://)([^/\s,]+)?([^ \s,]+)?)", "\\1[url=\\2\\4\\5]\\5[/url] [\\4]", $text );

    // No funky stuff
    $text = htmlspecialchars( $text );

    // Substitute newlines to breaks.
    $text = preg_replace('((\r)?\n)', "<br />", $text );

    // Substitute double breaks to paragraphs.
    $text = preg_replace('(<br /><br />)', "</p>\n\n<p>\n", $text );

    // Begin and end parapgraphs.
    $text = "<p>\n$text</p>\n";

    // YouTube support.
    $youTubeEmbed = "<iframe class=\"youtube-player\" type=\"text/html\" src=\"http://www.youtube.com/embed/\\1\" frameborder=\"0\"></iframe>\n";
    $text = preg_replace( '(\[youtube\]([^\[\]]+)\[/youtube\])', $youTubeEmbed, $text );

    // Replace [url=link]title[/url]
    $text = preg_replace( '(\[url=([^\[\]]+)\]([^\[\]]+)+\[/url\])', "<a href=\"\\1\">\\2</a>", $text );
    // Replace [url=link][/url]
    $text = preg_replace( '(\[url=([^\[\]]+)\]\[/url\] \[([^\[\]]+)+\])', "<a href=\"\\1\">\\1</a>", $text );
    // Replace [url]url[/url]
    $text = preg_replace( '(\[url\]([^\[\]]+)\[/url\])', "<a href=\"\\1\">\\1</a>", $text );
    // rel=nofollow for external links
    $text = preg_replace( '(<a href="http(s)?)', "<a rel=\"nofollow\" href=\"http\\1", $text );

    // Replace [i]text[/i]
    $text = preg_replace( '(\[i\]([^\[\]]+)\[/i\])', "<em>\\1</em>", $text );
    // Replace [b]text[/b]
    $text = preg_replace( '(\[b\]([^\[\]]+)\[/b\])', "<strong>\\1</strong>", $text );
    // Replace [s]text[/s]
    $text = preg_replace( '(\[s\]([^\[\]]+)\[/s\])', "<span class=\"strike\">\\1</span>", $text );
    // Replace [q]text[/q]
    $text = preg_replace( '(\[q\]([^\[\]]+)\[/q\])', "<q>\\1</q>", $text );
    // Replace [quote]text[/quote]
    $text = preg_replace( '(\[quote\]([^\[\]]+)\[/quote\])', "<blockquote>\\1</blockquote>", $text );

    // Replace [code]text[/code] for use with prettify.js
    $text = preg_replace( '(\[code\]([^\[\]]+)\[/code\])', "<pre><code>\\1</code></pre>", $text );

    if ( $authorMode )
    {
        // Replace [img]img[/img]
        $text = preg_replace( '(\[img\]([^\[\]]+)\[/img\])', "<div class=\"center\"><img src=\"\\1\" alt=\"\\1\" style=\"width: 99%\" /></div>", $text );
        // Replace [blockquote]text[/blockquote]
        $text = preg_replace( '((<p>)?(\n)*\[blockquote\](<br />)?([^\[\]]+)\[/blockquote\](<br />)?(\n)*(</p>)?)', "<blockquote><p>\\4</p></blockquote>", $text );
        // Replace [(/)ul|li]
        $text = preg_replace( '((<p>)?(\n)*\[(/)?(ol|ul|li)\](<br />)?(\n)*(</p>)?)', "<\\3\\4>", $text );

        // Complete relative links
        if ( $fullURLs )
        {
            $text = preg_replace( '(<a href=\"/)', "<a href=\"http://". $_SERVER["SERVER_NAME"]. "/", $text );
            $text = preg_replace( '(<img src=\"/)', "<img src=\"http://". $_SERVER["SERVER_NAME"]. "/", $text );
        }
    }

    // Allow &#91; and &#92; so admins (read: all users) can post [ and ] tags.
    $text = preg_replace( '(&amp;#91;)', "[", $text );
    $text = preg_replace( '(&amp;#92;)', "]", $text );
        
    // $text = $this->toSmiley( $text );

    return $text;
  }

  // Converts a string (such as a title) into a version safe for use in URIs
  public static function uriSafe( $uri )
  {
      $uri = iconv("utf-8", "ascii//TRANSLIT", $uri); // TRANSLIT does the whole job
      $uri = preg_replace('~[^\\pL0-9_]+~u', '-', $uri); // substitutes anything but letters, numbers and '_' with separator
      $uri = trim($uri, "-");
      $uri = strtolower($uri);
      $uri = preg_replace('~[^-a-z0-9_]+~', '', $uri); // keep only letters, numbers, '_' and separator
      return $uri;
  }

  // Removes BBcode markup from a string and returns a plain text version of the string
  public static function textStrip( $str )
  {
    $str = preg_replace( "(\[([^\[\]]+)\]([^\[\]]+)\[/([^\[\]]+)\])", "\\2", $str );

    // Twice, because of [ul][li] nests (and possibly others)
    $str = preg_replace( "(\[([^\[\]]+)\]([^\[\]]+)\[/([^\[\]]+)\])", "\\2", $str );

    return $str;
  }

  // Returns a summary of a larger string
  // TODO: deprecate and include teaser/cutoff functionality where needed
  public static function textSummary( $str, $maxLength = 250 )
  {
    $str = Misc::textStrip( $str );
    if ( strlen($str) > $maxLength )
    {
      $postfix = " ...";
      $maxLength -= strlen($postfix);
      $str = substr( $str, 0, $maxLength );
      $pos = strrpos( $str, " " );
      if ( $pos !== NULL )
        $str = substr( $str, 0, $pos );
        $str .= $postfix;
    }

    // TODO: we should probably have a textSummary plain and a textSummary specifically for articles
    // Substitute newlines to breaks.
    $str = preg_replace('((\r)?\n)', "<br />", $str );

    // Substitute double breaks to paragraphs.
    $str = preg_replace('(<br /><br />)', "</p>\n\n<p>\n", $str );
        
    return $str;
  }

}

?>
