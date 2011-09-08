<?

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
    if ( !$facebookUrl )
      $content .= Form::checkbox( "fbPublish", false, "Facebook", "Publish on Facebook" );
    if ( !$twitterUrl )
      $content .= Form::checkbox( "twPublish", false, "Twitter", "Publish on Twitter" );
    $content .= Form::button( "submit", "submit", "Opslaan" );
    $content .= Form::close();

    return $content;
  }

  public static function showSingle( &$db, &$user, $articleId, $json = false )
  {
    if ( $articleId )
    {
      $qId = $db->escape($articleId);
      $qUserId = $db->escape( $user->id );
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

  public static function sectionId( &$db, $section )
  {
    $q = $db->buildQuery( "select id,title from sections where id=%d or base_uri='/%s/'", $section, $section );
    Log::debug($q);
    $o = $db->getSingle($q);
    return $o ? $o->id : 0;
  }

  public static function sectionTitle( &$db, &$user, $section )
  {
    $q = $db->buildQuery( "select id,title from sections where id=%d or base_uri='/%s/'", $section, $section );
    Log::debug($q);
    $o = $db->getSingle($q);
    return $o ? $o->title : null;
  }
        
  public static function showMulti( &$db, &$user, $sectionId, $maxLength=1, $lengthInParagraphs=true )
  {
    $qUserId = $db->escape( $user->id );
    $qSection = $db->escape( $sectionId );
    $q = "select id,ctime,section_id,user_id,title,cname,body,visible,facebook_url,twitter_url from articles where section_id=$qSection and (visible=1 or user_id=$qUserId) and ctime<=now() order by ctime desc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while ( $o = $db->fetchObject($rs) )
        echo Articles::showArticle( $user, $o, false, $maxLength, $lengthInParagraphs );
  }

  public static function url( &$db, $sectionId, $cname )
  {
    $qSection = $db->escape( $sectionId );
    $sectionBaseUri = $db->getSingleValue( "select base_uri from sections where id=$qSection" );
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

    list( $type, $name, $pic ) = $uAuthor->socialData();
    $author = $name;
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
      $content .= "<span class=\"body\">$body</span>";

    $content .= "<footer>\n";
    $content .= "<ul>\n";
    
    if ( $o->facebook_url )
      $content .= "<li><a href=\"$o->facebook_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/facebook_16.png\" alt=\"\" /></a> <a href=\"$o->facebook_url\">Bekijk op Facebook</a></li>\n";
    if ( $o->twitter_url )
      $content .= "<li><a href=\"$o->twitter_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/twitter_16.png\" alt=\"\" /></a> <a href=\"$o->twitter_url\">Bekijk op Twitter</a></li>\n";

    // FIXME: doesn't degrade without js
    if ( $user->id == $o->user_id )
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

  public static function save( &$db, &$user )
  {
    global $errors;
    $errors = array();
    if ( !$user->id )
      $errors[] = "Je bent niet ingelogd.";

    $articleId = (int)$_POST['articleId'];
    $qId = $db->escape( $articleId );
    $qSection = $db->escape( (int)$_POST['sectionId'] );
    $qUserId = $db->escape( $user->id );

    list( $date, $time ) = explode( " ", $_POST['ctime'] );
    list( $day, $month, $year ) = explode( "-", $date );

    $ctime = "$year-$month-$day $time";
    $qCtime = $db->escape( $ctime );
    $qIp = $db->escape( $_SERVER['REMOTE_ADDR'] );
    $qTitle = $db->escape( $title = $_POST['title'] );
    $qCname = $db->escape( $cname = Misc::uriSafe($title) );
    $qBody = $db->escape( $_POST['body'] );
    $qVisible = (isset($_POST['visible']) && $_POST['visible']=='on') ? 1 : 0;
    $qFacebookUrl = $db->escape( $_POST['facebookUrl'] );
    $qTwitterUrl = $db->escape( $_POST['twitterUrl'] );
    
    if ( !$qBody )
      $errors[] = "Je kunt geen leeg artikel opslaan!";

    if ( sizeof($errors) )
      return $articleId;

    // TODO: use SocialUpdate::postLink
    $myUrl = Articles::url( $db, $qSection, $cname );
    $tinyUrl = TinyUrl::get( $myUrl );

    if ( isset($_POST['fbPublish']) && $_POST['fbPublish'] == 'on' )
    {
      global $fb;
      $msg = '';
      $link = $myUrl;
      $caption = $_SERVER['SERVER_NAME'];
      $description = Misc::textSummary( $_POST['body'], 400 );
      $picture = $picture ? $picture : Config::$headerLogo;
      Log::debug( "Article::fbPublish( $msg, $link, $title, $caption, $description, $picture );" );
      $fbRs = $user->fbUser->post( $msg, $link, $title, $caption, $description, $picture );
      $qFacebookUrl = $fbRs->url;
    }

    if ( isset($_POST['twPublish']) && $_POST['twPublish'] == 'on' )
    {
      $msg = "$title $tinyUrl";
      Log::debug( "Article::twPublish( $msg );" );
      $twRs = $user->twUser->post( $msg );
      $qTwitterUrl = $twRs->url;
    }

    $q = "select id from articles where id=$qId";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
    {
      $q = "update articles set ctime='$qCtime', mtime=now(), ip_addr='$qIp', section_id=$qSection, user_id=$qUserId, title='$qTitle', cname='$qCname', body='$qBody', visible=$qVisible, facebook_url='$qFacebookUrl', twitter_url='$qTwitterUrl' where id=$qId";
      $db->query($q);
    }
    else
    {
      $q = "insert into articles (ctime, mtime, ip_addr, section_id, user_id, title, cname, body, visible, facebook_url, twitter_url) values ('$qCtime', now(), '$qIp', $qSection, $qUserId, '$qTitle', '$qCname', '$qBody', $qVisible, '$qFacebookUrl', '$qTwitterUrl')";
      $rs = $db->query($q);
      $articleId = $db->lastInsertId($rs);
    }

    return $articleId;
  }
}

?>