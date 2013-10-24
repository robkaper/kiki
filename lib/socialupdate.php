<?php

class SocialUpdate extends Object
{
	private $body = null;

  public function reset()
  {
    parent::reset();
    $this->body = null;
  }

  public function load( $id = 0 )
  {
		if ( $id )
		{
			$this->id = $id;
			$this->objectId = 0;
		}

		$qFields = "publication_id AS id, o.object_id, o.user_id, o.ctime, o.mtime, body";
		$q = $this->db->buildQuery( "SELECT $qFields FROM publications p LEFT JOIN objects o ON o.object_id=p.object_id WHERE publication_id=%d OR o.object_id=%d", $this->id, $this->objectId );
		$this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    parent::setFromObject($o);

		// FIXME: rjkcust assuming only User 1 posts updates. Should be derived from publication connection reference.
    $this->userId = $o->user_id;

		$body = @unserialize($o->body);
		if ( $body !== false )
			$this->body = $body['message'];
		else
			$this->body = $o->body;
  }

  public function dbUpdate()
  {
    parent::dbUpdate();
  }
  
  public function dbInsert()
  {
  }

  public function url()
  {
    return 'socialupdate-'. $this->id;
  }

	public function templateData()
	{
		$uAuthor = ObjectCache::getByType( 'User', $this->userId );

    $data = array(
      'id' => $this->id,
      'url' => $this->url(),
      'ctime' => strtotime($this->ctime),
      'relTime' => Misc::relativeTime($this->ctime),
      'title' => $this->title(),
      'body' => $this->body,
      'author' => $uAuthor->name(),
      'publications' => array(),
      'likes' => $this->likes(),
      'comments' => Comments::count( $this->db, Kiki::getUser(), $this->objectId ),
      'html' => array(
        'comments' => Comments::show( $this->db, Kiki::getUser(), $this->objectId )
      )
    );
    
    $publications = $this->publications();
    foreach( $publications as $publication )
      $data['publications'][] = $publication->templateData();

    return $data;
  }

	public function title() {	return Misc::textSummary( $this->body, 40 ); }
	public function body() { return $this->body; }
}
