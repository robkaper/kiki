<?php

class Controller_Album extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    $path = explode( "/", $this->objectId );
 
    $albumId = 0;
    $pictureId = 0;

    if ( count($path) == 2 )
      list( $albumId, $pictureId ) = $path;
    else if ( count($path) == 1 )
      list( $albumId) = $path;
 
    if ( $albumId )
    {
      $album = new Album( $albumId );
      if ( $album->id() )
      {
        $this->template = 'pages/default';
        $this->status = 200;
        $this->title = "Album: ". $album->title();
        $this->content = $album->show($pictureId);
        
        return true;
      }
    }

    return false;
  }
}
  
?>