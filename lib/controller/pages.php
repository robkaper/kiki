<?php

class Controller_Pages extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    if ( !$this->objectId )
      $this->objectId = 'index';

    // Find page under this section through subcontroller.
    // TODO: also find subsections, instead of defining full paths in sections db...
    $this->subController = Router::findPage( $this->objectId, $this->instanceId );
		if ( $this->subController )
    {
      $this->subController->exec();
    }
    else if ( $this->objectId == 'index' )
    {
      $section = new Section( $this->instanceId );

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