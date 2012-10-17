<?php

  $this->title = "Albums";
  
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
    $album = new Album( $id );
    echo $album->form( $user );
  }
  else
  {
    echo "<table>\n";
    echo "<thead>\n";

    echo "<tr>\n"; 
    echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\" /></a></td>\n";
    echo "<td>". _("Create a new album"). "</td>\n";
    echo "</tr>\n";

    echo "<tr><th></th><th></th><th></th><th>Title</th></tr>\n";

    echo "</thead>\n";
    echo "<tbody>\n";

    $q = "SELECT id from albums where system=false ORDER BY id desc LIMIT 25";
    $rs = $db->query($q);
    if ( $db->numRows($rs) )
    {
      while( $o = $db->fetchObject($rs) )
      {
        $album = new Album( $o->id );
        $class = ""; // $album->visible() ? "" : "disabled";
        echo "<tr class=\"$class\">\n"; 
        echo "<td><a href=\"?id=". $album->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\" /></a></td>\n";
        echo "<td><a href=\"". $album->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\" /></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\" /></a></td>\n";
        echo "<td>". $album->title(). "</td>\n";
        echo "</tr>\n";
      }
    }
    echo "</tbody>\n";
    echo "</table>\n";
  }

  $this->content = ob_get_clean();
?>