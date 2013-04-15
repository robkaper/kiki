<?php

/**
 * Utility class for displaying post collections (articles and pages).
 *
 * @class Section
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */
            
class Section
{
  private $id = 0;
  private $baseURI = null;
  private $title = null;
  private $type = null;

  public function __construct( $id = 0 )
  {
    $this->db = Kiki::getDb();

    $this->id = $id;
    if ( $this->id )
      $this->load();
    else
      $this->reset();
  }

  public function reset()
  {
    $this->id = 0;
    $this->baseURI = null;
    $this->title = null;
    $this->type = null;
  }

  public function load( $id = 0 )
  {
    if ( $id )
      $this->id = $id;

    $qFields = "id, base_uri, title, type";
    $q = $this->db->buildQuery( "SELECT $qFields FROM sections WHERE id=%d", $this->id );
    $this->setFromObject( $this->db->getSingle($q) );
  }

  public function setFromObject( &$o )
  {
    if ( !$o )
      return;

    $this->id = $o->id;
    $this->baseURI = $o->base_uri;
    $this->title = $o->title;
    $this->type = $o->type;
  }

  public function save()
  {
    $this->id ? $this->dbUpdate() : $this->dbInsert();
  }

  public function dbUpdate()
  {
//    if ( !$this->baseURI || !$this->visible )
//      $this->baseURI = Misc::uriSafe($this->title);

    $q = $this->db->buildQuery(
      "UPDATE sections SET title='%s', base_uri='%s', type='%s' WHERE id=%d",
			$this->title, $this->baseURI, $this->type, $this->id
    );
    Log::debug($q);

    $this->db->query($q);
  }
  
  public function dbInsert()
  {
    if ( !$this->baseURI )
      $this->baseURI = Misc::uriSafe($this->title);

    $q = $this->db->buildQuery(
      "INSERT INTO sections (title,base_uri,type) values ('%s', '%s', '%s')",
        $this->title, $this->baseURI, $this->type
    );
    Log::debug($q);

    $rs = $this->db->query($q);
    if ( $rs )
      $this->id = $this->db->lastInsertId($rs);

    return $this->id;
  }

  public function id() { return $this->id; }
  public function setBaseURI( $baseURI ) { $this->baseURI = $baseURI; }
  public function baseURI() { return $this->baseURI; }
  public function setTitle( $title ) { $this->title = $title; }
  public function title() { return $this->title; }
  public function setType( $type ) { $this->type = $type; }
  public function type() { return $this->type; }

  /**
   * Creates an article edit form.
   *
   * @fixme Should be integrated into a template.
   *
   * @param User $user User object, used to show the proper connection links for publications.
   * @return string The form HTML.
   */
  public function form( &$user, $hidden=false )
  {
		$class = null;

    $content = Form::open( "sectionForm_". $this->id, Config::$kikiPrefix. "/json/section.php", 'POST', $class, "multipart/form-data" );
    $content .= Form::hidden( "sectionId", $this->id );
    $content .= Form::text( "baseURI", $this->baseURI, "URL name" );
    $content .= Form::text( "title", $this->title, "Title" );

    $types = array();
    $types['articles'] = 'Articles';
    $types['pages'] = 'Pages';

    $content .= Form::select( "type", $types, "Type", $this->type );

    $content .= Form::button( "submit", "submit", "Opslaan" );
    $content .= Form::close();

    return $content;
  }
}

?>