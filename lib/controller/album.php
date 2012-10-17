<?php

  // TODO: /kiki/album/
  //RewriteRule ^/kiki/album(/)?$ /www/git/kiki/htdocs/album/index.php [L]
  //RewriteRule ^/kiki/album/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,L]
  //RewriteRule ^/kiki/album/([^/]+)/([^/]+)(/)?$ /www/git/kiki/htdocs/album/index.php [E=albumId:$1,E=pictureId:$2,L]
  // TODO: test missing directoryindices (most notably / when moving content to database)

class Controller_Album extends Controller
{
  public function exec()
  {
    $db = $GLOBALS['db'];
    $user = $GLOBALS['user'];

    list( $albumId, $pictureId ) = explode( "/", $this->objectId );

    if ( $albumId )
    {
      $album = new Album( $albumId );
      if ( $album->id )
      {
        $this->template = 'pages/default';
        $this->status = 200;
        $this->title = "Album: $album->title";

        ob_start();
        $album->show($pictureId);
        $this->content = ob_get_contents();
        ob_end_clean();
        
        return true;
      }
    }

    return false;
  }
}
  
?>