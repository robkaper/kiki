<?php

namespace Kiki\Controller;

class Pages extends \Kiki\Controller
{
  public function exec()
  {
    $db = \Kiki\Core::getDb();
    $user = \Kiki\Core::getUser();

    if ( !$this->objectId )
      $this->objectId = 'index';

    // Find page under this section through subcontroller.

    // TODO: also find subsections, instead of defining full paths in
    // sections db...  the latter is faster, but then the base paths are not
    // properly normalised.  Both should be possible.

    $this->subController = \Kiki\Router::findPage( $this->objectId, $this->instanceId );
		if ( $this->subController )
    {
      $this->subController->exec();
    }
    else if ( $this->objectId == 'index' )
    {
      $section = new \Kiki\Section( $this->instanceId );

      $this->status = 200;
      $this->template = 'pages/autoindex';
      $this->title = sprintf( _("Index of %s"), $section->title() );

			$q = $db->buildQuery( "SELECT cname,title FROM articles a, objects o WHERE a.object_id=o.object_id AND o.section_id=%d AND visible=true", $this->instanceId );
			$rs = $db->query($q);

			if ( $db->numRows($rs) == 0 )
			{
				$this->template = 'pages/autoindex-empty';
				return;
			}

			$this->content = "<ul>";

			while( $o = $db->fetchObject($rs) )
			{
				$this->content .= sprintf( '<li><a href="%s">%s</a></li>', $o->cname, $o->title );
			}

			$this->content .= "</ul>";
    }
  }
}
  
?>