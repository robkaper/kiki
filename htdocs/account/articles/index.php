<?
  $template = Template::getInstance();

  if ( !$user->isAdmin() )
  {
    $template->load( 'pages/admin-required' );
    echo $template->content();
    exit();
  }

  $template->load( 'pages/admin' );

  $template->assign( 'title', "Articles" );

  ob_start();

  if ( isset($_GET['id']) )
  {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $article = new Article( $id );
    $album = new Album( $article->albumId() );

    // Create album for this article if it doesn't exist yet.
    if ( !$album->id() )
    {
      $album->setSystem(true);
      $album->setTitle( $article->title() );
      $album->save();

      $article->setAlbumId($album->id());
      if ( $article->id() )
        $article->save();

      if ( $album->id() && $article->headerImage() )
      {
        $pictures = $album->addPictures( null, null, array($article->headerImage()) );
        Log::debug( "article had headerImage (". $article->headerImage(). ") that was not a picture in an album, added it to album ". $album->id() );
      }
    }

    echo $article->form( $user );
    if ( $album->id() )
      echo $album->form( $user );
  }
  else
  {
    echo "<table>\n";
    echo "<thead>\n";

    echo "<tr>\n"; 
    echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\" /></a></td>\n";
    echo "<td colspan=\"2\">". _("Create a new article"). "</td>\n";
    echo "</tr>\n";

    echo "<tr><th></th><th></th><th></th><th>URL</th><th>Title</th></tr>\n";

    echo "</thead>\n";
    echo "<tbody>\n";

    $q = "SELECT a.id FROM articles a LEFT JOIN sections s ON s.id=a.section_id WHERE s.type='articles' ORDER BY ctime desc LIMIT 25";
    $rs = $db->query($q);
    if ( $db->numRows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $article = new Article( $o->id );
        $class = $article->visible() ? "" : "disabled";
        echo "<tr class=\"$class\">\n"; 
        echo "<td><a href=\"?id=". $article->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\" /></a></td>\n";
        echo "<td><a href=\"". $article->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\" /></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\" /></a></td>\n";
        echo "<td>". $article->cname(). "</td>\n";
        echo "<td>". $article->title(). "</td>\n";
        echo "</tr>\n";
      }
    }

    echo "</tbody>\n";
    echo "</table>\n";
  }

  $template->assign( 'content', ob_get_clean() );
  echo $template->content();
?>