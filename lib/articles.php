<?

/**
 * Utility class for displaying article collections.
 *
 * @class Articles Utility class for displaying article collections.
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */
            
class Articles
{
  public static function showSingle( &$db, &$user, $articleId, $json = false, $showAsPage = false )
  {
    if ( $articleId )
    {
      $qId = $db->escape($articleId);
      $qUserId = $db->escape( $user->id() );
      $qWhere = is_numeric($articleId) ? "id=$qId" : "cname='$qId'";
      $q = "select object_id,id,ctime,section_id,user_id,title,cname,body,header_image,featured,visible from articles where $qWhere and (visible=1 or user_id=$qUserId)";
      $o = $db->getSingle($q);
    }
    else
    {
      $o = new stdClass;
      $o->id = 0;
      $o->title = "...";
      $o->body = "...";
      $o->header_image = 0;
      $o->featured = false;
      $o->date = time();
      $o->user_id = $user->id();
    }

    return $o ? Articles::showArticle( $user, $o, $json, 0, false, $showAsPage ) : null;
  }

  public static function sectionTitle( &$db, &$user, $sectionId )
  {
    $q = $db->buildQuery( "select id,title from sections where id=%d", $sectionId );
    $o = $db->getSingle($q);
    return $o ? $o->title : null;
  }
        
  public static function showMulti( &$db, &$user, $sectionId, $maxItems=10, $maxLength=1, $lengthInParagraphs=true )
  {
    $content = MultiBanner::articles( $sectionId );

    $qUserId = $db->escape( $user->id() );
    $qSection = $db->escape( $sectionId );
    $q = "select object_id,id,ctime,section_id,user_id,title,cname,body,header_image,featured,visible from articles where section_id=$qSection and ( (visible=1 and ctime<=now()) or user_id=$qUserId) order by ctime desc";
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

  public static function showArticle( &$user, &$o, $json = false, $maxLength=0, $lengthInParagraphs=false, $showAsPage=false )
  {
    $content = "";
    $article = new Article( $o->id );

    $title = $o->title;
    if ( !$o->visible )
      $title .= " (unpublished)";
    $myUrl = Articles::url( $GLOBALS['db'], $o->section_id, $o->cname );

    // Expirimental: parse body as template first.
    // FIXME: for performance reasons, don't do this when no template syntax ({ and }) is used in the body.
    $template = new Template();
    $template->setContent($o->body);
    $template->setCleanup(false);
    $body = $template->fetch();

    $body = Misc::markup( $body );

    $date = $o->ctime;

    $uAuthor = ObjectCache::getByType( 'User', $o->user_id );
    $author = $uAuthor->name();
    $pic = $uAuthor->picture();
    $relTime = Misc::relativeTime($date);
    $dateTime = date("c", strtotime($date));

    if ( !$showAsPage )
    {
      if ( !$json )
        $content .= "<article id=\"article_". $o->id. "\">\n";
    
      $content .= "<header>\n";
      if ( $maxLength )
        $content .= "<h2><span><a href=\"$myUrl\">$title</a></span></h2>\n";

      if ( !$maxLength && $o->header_image )
      {
        $img = Storage::url( $o->header_image );
        list ($base, $ext) = Storage::splitExtension( $img );
        $img = "${base}.780x400.c.${ext}";
        $content .= "<img src=\"$img\" />\n";
      }

      $content .= "<span class=\"author\">$author</span>\n";
      $content .= "<time class=\"relTime\" datetime=\"$dateTime\">$relTime geleden</time>\n";

      $content .= "</header>\n";

      if ( $maxLength )
      {
        $content .= "<p>\n". Misc::textSummary( $o->body, $maxLength, $lengthInParagraphs ). "</p>\n";
        // $content .= "<a href=\"$myUrl\" class=\"button\" style=\"float: right;\">". _("Read more"). "</a></p>\n";
      }
      else
        $content .= "<span class=\"body\">$body</span>";
    }
    else
      $content .= $body;

    $content .= $showAsPage ? "<footer class=\"pageFooter\">\n" : "<footer>\n";
    $content .= "<ul>\n";

    $publications = $article->publications();
    foreach( $publications as $publication )
    {
      $content .= "<li><a href=\"". $publication->url(). "\" class=\"button\"><span class=\"buttonImg ". $publication->service(). "\"></span>". $publication->service(). "</a></li>\n";
    }

    if ( $maxLength )
      $content .= "<li><a href=\"$myUrl\" class=\"button\">". _("Read more"). "</a></li>\n";

    // FIXME: doesn't degrade without js
    if ( !$maxLength && $user->id() == $o->user_id )
      $content .= "<li><a href=\"javascript:showArticleForm($o->id);\" class=\"button\">Wijzigen</a></li>\n";

    // $content .= "<li><a href=\"javascript:showArticleComments($o->id);\">Reacties</a></li>\n";

    $content .= "</ul>\n";
    $content .= "</footer>\n";

    if ( !$maxLength && $user->id() == $o->user_id )
      $content .= $article->form( $user, true, $showAsPage ? 'pages' : 'articles' );

    // FIXME: page/filter comments in embedded view
    if  ( !$maxLength && !$showAsPage )
    {
      // $content .= Comments::show( $GLOBALS['db'], $GLOBALS['user'], $o->id );
      $content .= Comments::show( $GLOBALS['db'], $GLOBALS['user'], $o->object_id );
    }

    if ( !$json && !$showAsPage )
       $content .= "</article>\n";

    return $content;
  }
}

?>