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

    // $content .= Form::attachFile( "Image", "body" );
          
    return $content;
  }

  public static function showSingle( &$db, &$user, $articleId, $json = false )
  {
    $qId = $db->escape($articleId);
    $qWhere = is_numeric($articleId) ? "id=$qId" : "cname='$qId'";
    $q = "select id,ctime,section_id,user_id,title,cname,body,visible,facebook_url,twitter_url from articles where $qWhere";
    $o = $db->getSingle($q);
    return $o ? Articles::showArticle( $user, $o, $json ) : null;
  }

  public static function title( &$db, &$user, $articleId )
  {
    $qId = $db->escape( $articleId );
    return $db->getSingleValue( "select title from articles where id='$qId' or cname='$qId'" );
  }

  public static function showMulti( &$db, &$user, $sectionId )
  {
    $qSection = $db->escape( $sectionId );
    $q = "select id,ctime,section_id,user_id,title,cname,body,visible,facebook_url,twitter_url from articles where section_id=$qSection and visible=1 and ctime<=now() order by ctime desc";
    $rs = $db->query($q);
    if ( $rs && $db->numRows($rs) )
      while ( $o = $db->fetchObject($rs) )
        echo Articles::showArticle( $user, $o );
  }

  public static function showArticle( &$user, &$o, $json = false )
  {
    $content = "";

    $title = $o->title;
    $body = Misc::markup( $o->body );
    $date = $o->ctime;
    $uAuthor = new User( $o->user_id );

    list( $type, $name, $pic ) = $uAuthor->socialData();
    $author = $name;
    $relTime = Misc::relativeTime($date);

    if ( !$json )
      $content .= "<article id=\"article_". $o->id. "\">\n";

    $content .= "<header>\n";
    $content .= "<h2><span>$title</span></h2>\n";
    $content .= "<span class=\"author\">$author</span>\n";
    $content .= "<time class=\"relTime\" datetime=\"$date\">$relTime geleden</time>\n";
    $content .= "</header>\n";
    $content .= $body;
    $content .= "<footer>\n";

    if ( $o->facebook_url )
      $content .= "<li><a href=\"$o->facebook_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/facebook_16.png\" /></a> <a href=\"$o->facebook_url\">Bekijk op Facebook</a></li>\n";
    if ( $o->twitter_url )
      $content .= "<li><a href=\"$o->twitter_url\"><img src=\"". Config::$kikiPrefix. "/img/komodo/twitter_16.png\" /></a> <a href=\"$o->twitter_url\">Bekijk op Twitter</a></li>\n";

    // FIXME: doesn't degrade without js
    if ( $user->id == $o->user_id )
      $content .= "<li><a href=\"javascript:showArticleForm($o->id);\">Wijzigen</a></li>\n";

    // $content .= "<li><a href=\"javascript:showArticleComments($o->id);\">Reacties</a></li>\n";

    $content .= "</ul>\n";
    $content .= "</footer>\n";

    $content .= Articles::form( $user, $o );
    // FIXME: page/filter comments in embedded view
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

    $articleId = $_POST['articleId'];
    $qId = $db->escape( $articleId );
    $qSection = $db->escape( $_POST['sectionId'] );
    $qUserId = $db->escape( $user->id );

    list( $date, $time ) = split( " ", $_POST['ctime'] );
    list( $day, $month, $year ) = split( "-", $date );

    $ctime = "$year-$month-$day $time";
    $qCtime = $db->escape( $ctime );
    $qIp = $db->escape( $_SERVER['REMOTE_ADDR'] );
    $qTitle = $db->escape( $title = $_POST['title'] );
    $qCname = $db->escape( $cname = Misc::uriSafe($title) );
    $qBody = $db->escape( $_POST['body'] );
    $qVisible = $_POST['visible']=='on' ? 1 : 0;
    $qFacebookUrl = $db->escape( $_POST['facebookUrl'] );
    $qTwitterUrl = $db->escape( $_POST['twitterUrl'] );
    
    if ( !$qBody )
      $errors[] = "Je kunt geen leeg artikel opslaan!";

    if ( sizeof($errors) )
      return $articleId;
      
    $sectionBaseUri = $db->getSingleValue( "select base_uri from sections where id=$qSection" );
    $urlPrefix = "http://". $_SERVER['SERVER_NAME'];
    $myUrl = $urlPrefix. $sectionBaseUri. $cname;
    Log::debug( "myUrl: [$myUrl] -- [$urlPrefix][$sectionBaseUri][$cname]" );

    $tinyUrl = TinyUrl::get( $sectionBaseUri. $cname );
    Log::debug( "tinyUrl: [$tinyUrl]" );

    if ( isset($_POST['fbPublish']) && $_POST['fbPublish'] == 'on' )
    {
      global $fb;
      $msg = '';
      $link = $myUrl;
      $caption = '';
      $description = '';
      $picture = $picture ? $picture : Config::$headerLogo;
      Log::debug( "Social::fbPublish( $msg, $link, $title, $caption, $description, $picture );" );
      $fbRs = Social::fbPublish( $fb, $msg, $link, $title, $caption, $description, $picture );
      $qFacebookUrl = $fbRs->url;
    }

    if ( isset($_POST['twPublish']) && $_POST['twPublish'] == 'on' )
    {
      global $tw;
      $msg = "$title $tinyUrl";
      Log::debug( "Social::twPublish( $msg );" );
      $twRs = Social::twPublish( $tw, $msg );
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
    Log::debug( $q );

    return $articleId;
  }
}

?>