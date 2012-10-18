<?php

  $this->title = "Sections";

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $this->template = 'pages/admin';

  ob_start();

  if ( isset($_GET['id']) )
  {
    $section = new Section( $id );
    echo $section->form( $user );
  }
  else
  {
    echo "<table>\n";
    echo "<thead>\n";

    echo "<tr>\n"; 
    echo "<td colspan=\"3\"><a href=\"?id=0\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"New\"></a></td>\n";
    echo "<td colspan=\"3\">". _("Create a new section"). "</td>\n";
    echo "</tr>\n";

    echo "<tr><th></th><th></th><th></th><th>URL</th><th>Title</th><th>Type</th></tr>\n";

    echo "</thead>\n";
    echo "<tbody>\n";

    $q = "SELECT id,base_uri,title,type from sections ORDER BY base_uri ASC";
    $rs = $db->query($q);
    if ( $db->numRows($rs) )
    {
      $section = new Section();
      while( $o = $db->fetchObject($rs) )
      {
        $section->setFromObject($o);
        echo "<tr>\n"; 
        echo "<td><a href=\"?id=". $section->id(). "\"><img src=\"/kiki/img/iconic/black/pen_alt_fill_16x16.png\" alt=\"Edit\"></a></td>\n";
        echo "<td></td>\n"; // <a href=\"". $section->url(). "\"><img src=\"/kiki/img/iconic/black/magnifying_glass_16x16.png\" alt=\"View\"></a></td>\n";
        echo "<td><a href=\"\"><img src=\"/kiki/img/iconic/black/trash_stroke_16x16.png\" alt=\"Delete\"></a></td>\n";
        echo "<td>". $section->baseURI(). "</td>\n";
        echo "<td>". $section->title(). "</td>\n";
        echo "<td>". $section->type(). "</td>\n";
        echo "</tr>\n";
      }
    }
    echo "</tbody>\n";
    echo "</table>\n";
  }

  $this->content = ob_get_clean();
?>