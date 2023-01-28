<?php

/**
 * Utility class for various methods that don't fit Kiki's object model (yet).
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 *
 * @todo refactor markup related methods to a Markup (or BBcode) class.
 */

namespace Kiki;

class Misc
{
  /**
  * Provides a description of the difference between a timestamp and the current time.
  * @param int $time the time to compare the current time to
  * @return string description of the difference in time
  * @warning Only supports comparison to times in the past.
  * @todo add i18n support
  */
  public static function relativeTime( $time, $now = null )
  {
    if ( $now === null )
      $now = time();
    if ( !is_numeric($time) )
      $time = strtotime( $time );
    $delta = $now - $time;
    $absDelta = abs($delta);

    if ( $absDelta < 60 )
      return "less than a minute";
    else if ( $absDelta < 120 )
      return "one minute";
    else if ( $absDelta < (4*60) )
      return "a few minutes";
    else if ( $absDelta < (60*60) )
      return (int)($absDelta/60). " minutes";
    else if ( $absDelta < (120*60) )
      return "one hour";
    else if ( $absDelta < (24*60*60) )
      return (int)($absDelta/3600). " hours";
    else if ( $absDelta < (48*60*60) )
      return "one day";
    else
      return (int)($absDelta/86400). " days";
  }

  /**
  * Converts text (plain or with BBcode markup into formatted HTML.
  * @param string $text input text
  * @param boolean $authorMode (optional) whether to allow images, blockquotes and lists
  * @param boolean $fullURLs (optional) whether to allow the use of relative URLs
  * @return string the HTML formatted text
  */
  public static function markup( $text, $authorMode = true, $fullURLs = false )
  {
    $matches = array();
    $htmlTags = preg_match_all( '~</?[^>]+>~', $text, $matches );
    if ( $htmlTags )
    {
      $bbTags = preg_match_all( '~\[/?[^\]]+\]~', $text, $matches );
      if ( $htmlTags > $bbTags )
        return $text;
    }

    // Turn ordinary URLs into [url]
    $text = preg_replace( "((\s)(http(s)?\://)([^/\s,]+)?([^ \s,]+)?)", "\\1[url=\\2\\4\\5]\\5[/url] [\\4]", $text );

    // No funky stuff
    $text = htmlspecialchars( $text );

    // Substitute newlines to breaks.
    $text = preg_replace('((\r)?\n)', "<br>", $text );

    // Substitute double breaks to paragraphs.
    $text = preg_replace('(<br><br>)', "</p>\n\n<p>\n", $text );

    // Begin and end parapgraphs.
    $text = "<p>\n$text</p>\n";

    // YouTube support.
    $youTubeEmbed = "<iframe class=\"youtube-player\" type=\"text/html\" src=\"//www.youtube.com/embed/\\1\" frameborder=\"0\"></iframe>\n";
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

		// Album images (should this be here? I don't think so...)
    // Replace [image]1-based id in gallery[/image]
    $text = preg_replace_callback( '~(\[image\]([^\[\]]+)\[/image\])~', array('self', 'markupImages'), $text );

    // Replace [code]text[/code] for use with prettify.js
    $text = preg_replace( '(\[code\]([^\[\]]+)\[/code\])', "<pre><code>\\1</code></pre>", $text );

    if ( $authorMode )
    {
        // Replace [img]img[/img]
        $text = preg_replace( '(\[img\]([^\[\]]+)\[/img\])', "<div class=\"center\"><img src=\"\\1\" alt=\"\\1\" style=\"width: 99%\"></div>", $text );
        // Replace [blockquote]text[/blockquote]
        $text = preg_replace( '((<p>)?(\n)*\[blockquote\](<br>)?([^\[\]]+)\[/blockquote\](<br>)?(\n)*(</p>)?)', "<blockquote><p>\\4</p></blockquote>", $text );
        // Replace [(/)ul|li]
        $text = preg_replace( '((<p>)?(\n)*\[(/)?(ol|ul|li)\](<br>)?(\n)*(</p>)?)', "<\\3\\4>", $text );

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

	private static function markupImages( $input )
	{
		$imgId = (int) $input[2];
		$imgId--;

		$album = new Album( $GLOBALS['articleAlbumId'] );
		$imageUrls = $album->imageUrls();

		$output = "<img src=\"". $imageUrls[$imgId]. "\" alt=\"\">";
		return $output;
	}


  /**
  * Converts a string into a version safe for use in URIs.
  * @param string $str input string
  * @return string the URI-safe string
  */
  public static function uriSafe( $str )
  {
      // Convert to ASCII with common translations
      $uri = iconv("utf-8", "ascii//TRANSLIT", $str);
      // Substitutes anything but letters, numbers and '_' with separator ('-')
      $uri = preg_replace('~[^\\pL0-9_]+~u', '-', $uri);
      // Remove seperators from the beginning and end
      $uri = trim($uri, "-");
      // Lowercase
      $uri = strtolower($uri);
      // Keep only letters, numbers, '_' and separator
      $uri = preg_replace('~[^-a-z0-9_]+~', '', $uri);
      return $uri;
  }

  /**
  * Removes BBcode markup from a text.
  * @param string $str input text
  * @return string plain text version of the text
  */
  public static function textStrip( $str )
  {
    $str = strip_tags($str);

    $str = preg_replace( "(\[([^\[\]]+)\]([^\[\]]+)\[/([^\[\]]+)\])", "\\2", $str );

    // Twice, because of [ul][li] nests (and possibly others)
    $str = preg_replace( "(\[([^\[\]]+)\]([^\[\]]+)\[/([^\[\]]+)\])", "\\2", $str );

    // Remove carriage returns.
    $str = preg_replace( "(\r)", "", $str );

    return $str;
  }

  /**
  * Provides a summary of a larger text.
  * @param string $str input text
  * @param int $maxLength maximum output length
  * @param bool $lengthInParagraphs whether the specified length means paragraphs or characters
  * @return string plain text version of the string
  * @todo keep for phrases that need to be shortened for Twitter/SMS/etc,
  *   but deprecate in article overviews and write teaster/cutoff
  *   functionality there
  */
  public static function textSummary( $str, $maxLength = 250, $lengthInParagraphs=false )
  {
    $postfix = " ...";

    $str = Misc::textStrip( $str );

    if ( $lengthInParagraphs )
    {
      $paragraphs = explode( "\n\n", $str );
      $keep = array_chunk( $paragraphs, $maxLength );
      $str = join( "\n\n", array_values($keep[0]) );
      if ( count($paragraphs) > $maxLength )
        $str .= $postfix;
    }
    else if ( strlen($str) > $maxLength )
    {
      $maxLength -= strlen($postfix);
      $str = substr( $str, 0, $maxLength );
      $pos = strrpos( $str, " " );
      if ( $pos !== NULL )
        $str = substr( $str, 0, $pos );
      $str .= $postfix;
    }

    // Substitute breaks for newlines.
    $str = preg_replace('((\r)?\n)', "<br>", $str );

    // Substitute paragraphs for double breaks.
    $str = preg_replace('(<br><br>)', "</p>\n\n<p>\n", $str );
        
    return $str;
  }

  public static function isMobileSafari()
  {
    return preg_match( '/(iPod|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT'] );
  }

  // $dates = array( '2012' => '2012-08-06' );
  public static function countdown( $datesPerYear )
  {
    $year = date("Y");
    $day = date("z");

    // Find date
    $dayTarget = isset($datesPerYear[$year]) ? strtotime($datesPerYear[$year]) : false;
    $yearDiff = 0;

    // Find date next year
    if ( $dayTarget === false )
    {
      $dayTarget = isset($datesPerYear[$year+1]) ? strtotime($datesPerYear[$year+1]) : false;
      $yearDiff = date("z", mktime(0,0,0,12,31,$year)) + 1;
    }

    Log::debug( "$dayTarget, $day, $yearDiff, ". date("z", $dayTarget) );
    if ( $dayTarget !== false )
      return date("z", $dayTarget) - $day + $yearDiff;
    
    return false;
  }

}
