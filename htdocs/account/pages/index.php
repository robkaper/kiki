<?php

	use Kiki\Article;
	use Kiki\Album;
	use Kiki\Section;

  $this->title = "Pages";

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $this->template = 'pages/admin';

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
    }

    echo $article->form( false, 'pages' );
    if ( $album->id() )
      echo $album->form();
  }
  else
  {
    $q = "SELECT a.id FROM articles a LEFT JOIN objects o ON o.object_id=a.object_id LEFT JOIN sections s ON s.id=o.section_id WHERE o.section_id=0 OR s.type='pages' ORDER BY s.base_uri ASC, FIELD(a.cname, 'index') ASC";
    $pages = $db->getObjectIds($q);

		echo "<ul>";
		echo "<li>";
    echo "<a href=\"?id=0\">". _("Create a new page"). "</a>\n";
		echo "</li>";
		echo "</ul>";

    echo "<table>\n";
    echo "<thead>\n";

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
        echo "<td><a href=\"?id=". $article->id(). "\">Edit</a></td>\n";
        echo "<td><a href=\"". $article->url(). "\">View</a></td>\n";
        echo "<td><a href=\"\">Delete</a></td>\n";
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

  $this->content = ob_get_clean();
?>