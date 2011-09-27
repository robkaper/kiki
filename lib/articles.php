

class Articles
{
  public static function form( &$user, &$o = null )
  {
    $section = $o ? $o->section_id : 0;
    $articleId = $o ? $o->id : 0;
    $twitterUrl = $o ? $o->twitter_url : 0;
    $facebookUrl = $o ? $o->facebook_url : 0;
    $title = $o ? $o->title : "";
    $body = $o ? htmlspecialchars( $o->body ) : "";
    $date = date( "d-m-Y H:i", $o ? strtotime($o->ctime) : time() );
    $visible = ($o && $o->visible);
    $class = $o ? "hidden" : "";

    $sections = array();
    $db = $GLOBALS['db'];
    $q = "select id,title from sections order by title asc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while( $oSection = $db->fetchObject($rs) )
        $sections[$oSection->id] = $oSection->title;

    $content = Form::open( "articleForm_${articleId}", Config::$kikiPrefix. "/json/article.php", 'POST', $class );
    $content .= Form::hidden( "articleId", $articleId );
    $content .= Form::hidden( "twitterUrl", $twitterUrl );
    $content .= Form::hidden( "facebookUrl", $facebookUrl );
    $content .= Form::select( "sectionId", $sections, "Section", $section );
    $content .= Form::text( "title", $title, "Title" );
    $content .= Form::datetime( "ctime", $date, "Date" );
    $content .= Form::textarea( "body", $body, "Body" );
    $content .= Form::checkbox( "visible", $visible, "Visible" );

    // @todo Make this generic, difference with social update is the check
    // against an already stored external URL.
    foreach ( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Facebook' )
      {
        // @todo inform user that, and why, these are required (offline
        // access is required because Kiki doesn't store or use the
        // short-lived login sessions)
        if ( !$connection->hasPerm('publish_stream') )
         continue;
        if ( !$connection->hasPerm('offline_access') )
         continue;

        if ( !$facebookUrl )
          $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
      }
      else if (  $connection->serviceName() == 'Twitter' )
      {
        $content .= Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
      }
    }

    $content .= Form::button( "submit", "submit", "Opslaan" );
    $content .= Form::close();

    return $content;
  }

  public static function showSingle( &$db, &$user, $articleId, $json = false )
  {
    if ( $articleId )
    {
      $qId = $db->escape($articleId);
      $qUserId = $db->escape( $user->id() );
      $qWhere = is_numeric($articleId) ? "id=$qId" : "cname='$qId'";
      $q = "select id,ctime,section_id,user_id,title,cname,body,visible,facebook_url,twitter_url from articles where $qWhere and (visible=1 or user_id=$qUserId)";
      $o = $db->getSingle($q);
    }
    else
    {
      $o = new stdClass;
      $o->id = 0;
      $o->title = "...";
      $o->body = "...";
      $o->date = time();
      $o->user_id = $user->id;
    }

    return $o ? Articles::showArticle( $user, $o, $json ) : null;
  }

  public static function title( &$db, &$user, $articleId )
  {
    $qId = $db->escape( $articleId );
    return $db->getSingleValue( "select title from articles where id='$qId' or cname='$qId'" );
  }

  public static function sectionTitle( &$db, &$user, $sectionId )
  {
    $q = $db->buildQuery( "select id,title from sections where id=%d", $sectionId );
    $o = $db->getSingle($q);
    return $o ? $o->title : null;
  }
        
  public static function showMulti( &$db, &$user, $sectionId, $maxLength=1, $lengthInParagraphs=true )
  {
    $content = "";

    $qUserId = $db->escape( $user->id() );
    $qSection = $db->escape( $sectionId );
    $q = "select id,ctime,section_id,user_id,title,cname,body,visible,facebook_url,twitter_url from articles where section_id=$qSection and (visible=1 or user_id=$qUserId) and ctime<=now() order by ctime desc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while ( $o = $db->fetchObject($rs) )
        $content .= Articles::showArticle( $user, $o, false, $maxLength, $lengthInParagraphs );

    return $content;
  }

  public static function url( &$db, $sectionId, $cname )
  {
    $sectionBaseUri = Router::getBaseUri( 'articles', $sectionId );
    if ( !$sectionBaseUri )
      $sectionBaseUri = "/";
    $urlPrefix = "http://". $_SERVER['SERVER_NAME'];
    $myUrl = $urlPrefix. $sectionBaseUri. $cname;
    return $myUrl;
  }

  public static function showArticle( &$user, &$o, $json = false, $maxLength=0, $lengthInParagraphs=false )
  {
    $content = "";

    $title = $o->title;
    if ( !$o->visible )
      $title .= " (unpublished)";
    $body = Misc::markup( $o->body );
    $date = $o->ctime;
    $uAuthor = new User( $o->user_id );

    $author = $uAuthor->name();
    $pic = $uAuthor->picture();
    $relTime = Misc::relativeTime($date);
    $dateTime = date("c", strtotime($date));

    if ( !$json )
      $content .= "<article id=\"article_". $o->id. "\">\n";

    $content .= "<header>\n";
    $content .= "<h2><span>$title</span></h2>\n";
    $content .= "<span class=\"author\">$author</span>\n";
    $content .= "<time class=\"relTime\" datetime=\"$dateTime\">$relTime geleden</time>\n";
    $content .= "</header>\n";

    if ( $maxLength )
    {
      $myUrl = Articles::url( $GLOBALS['db'], $o->section_id, $o->cname );
      $content .= "<p>\n". Misc::textSummary( $o->body, $maxLength, $lengthInParagraphs ). " <a href=\"$myUrl\">Lees verder</a></p>\n";
    }
    else
    {
      $content .= "<span class=\"body\">$body</span>";
    }

    $content .= "<footer>\n";
    $content .= "<ul>\n";
    
    if ( $o->facebook_url )
      $content .= "<li><a href=\"$o->facebook_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/User_Facebook_16.png\" alt=\"[Facebook]\" /></a></li>\n";
    if ( $o->twitter_url )
      $content .= "<li><a href=\"$o->twitter_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/User_Twitter_16.png\" alt=\"[Twitter]\" /></a></li>\n";

    // FIXME: doesn't degrade without js
    if ( !$maxLength && $user->id() == $o->user_id )
      $content .= "<li><a href=\"javascript:showArticleForm($o->id);\">Wijzigen</a></li>\n";

    // $content .= "<li><a href=\"javascript:showArticleComments($o->id);\">Reacties</a></li>\n";

    $content .= "</ul>\n";
    $content .= "</footer>\n";

    if ( !$maxLength )
      $content .= Articles::form( $user, $o );
    // FIXME: page/filter comments in embedded view
    if  ( !$maxLength )
      $content .= Comments::show( $GLOBALS['db'], $GLOBALS['user'], $o->id );

    if ( !$json )
       $content .= "</article>\n";

    return $content;
  }
}

?>