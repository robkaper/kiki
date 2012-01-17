<?
  require_once "../../../lib/init.php";

  $page = new AdminPage( "Pages" );
  $page->header();

  if ( isset($_GET['id']) )
  {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $article = new Article( $id );
    echo $article->form( $user );
  }
  else
  {
    $pages = Router::getBaseURIs( 'page', true );
    if ( count($pages ) )
    {
      echo "<table>\n";
      echo "<thead>\n";

      echo "<tr>\n"; 
      echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\" /></a></td>\n";
      echo "<td colspan=\"2\">". _("Create a new page"). "</td>\n";
      echo "</tr>\n";

      echo "<tr><th></th><th></th><th></th><th>URL</th><th>Title</th></tr>\n";

      echo "</thead>\n";
      echo "<tbody>\n";

      foreach ( $pages as $p )
      {
        $article = new Article( $p->instance_id );
        $class = $article->visible() ? "" : "disabled";
        echo "<tr class=\"$class\">\n"; 
        echo "<td><a href=\"?id=". $article->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\" /></a></td>\n";
        echo "<td><a href=\"". $article->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\" /></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\" /></a></td>\n";
        echo "<td>". $article->cname(). "</td>\n";
        echo "<td>". $article->title(). "</td>\n";
        echo "</tr>\n";
      }
      echo "</tbody>\n";
      echo "</table>\n";
    }
  }

  $page->footer();
?>