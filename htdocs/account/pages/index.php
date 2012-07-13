<?
  $template = Template::getInstance();

  if ( !$user->isAdmin() )
  {
    $template->load( 'pages/admin-required' );
    echo $template->content();
    exit();
  }

  $template->load( 'pages/admin' );

  $template->assign( 'title', "Pages" );

  ob_start();

  if ( isset($_GET['id']) )
  {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $article = new Article( $id );
    $album = new Album( $article->albumId() );

    // Create album for this page if it doesn't exist yet.
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

    echo $article->form( $user, false, 'pages' );
    if ( $album->id() )
      echo $album->form( $user );
  }
  else
  {
    $q = "SELECT a.id FROM articles a LEFT JOIN sections s ON s.id=a.section_id WHERE a.section_id=0 OR s.type='pages' ORDER BY s.base_uri ASC, FIELD(a.cname, 'index') ASC";
    $pages = $db->getArray($q);

    echo "<table>\n";
    echo "<thead>\n";

    echo "<tr>\n"; 
    echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\" /></a></td>\n";
    echo "<td colspan=\"3\">". _("Create a new page"). "</td>\n";
    echo "</tr>\n";

    echo "<tr><th></th><th></th><th></th></th><th colspan=\"2\">URL</th><th>Title</th></tr>\n";

    echo "</thead>\n";
    echo "<tbody>\n";

    if ( count($pages ) )
    {
      foreach ( $pages as $pageId )
      {
        $article = new Article( $pageId );
        $section = new Section( $article->sectionId() );
        $class = $article->visible() ? "" : "disabled";
        echo "<tr class=\"$class\">\n"; 
        echo "<td><a href=\"?id=". $article->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\" /></a></td>\n";
        echo "<td><a href=\"". $article->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\" /></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\" /></a></td>\n";
        if ( $section->baseURI() )
        {
          echo "<td>". $section->baseURI(). "</td>\n";
          echo "<td>". $article->cname(). "</td>\n";
        }
        else
        {
          echo "<td colspan=\"2\">". $article->cname(). "</td>\n";
        }
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