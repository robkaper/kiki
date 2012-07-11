<?
/**
 * Handles creation and modification requests (POSTS) for articles.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

//  require_once "../../lib/init.php";

  if ($_POST)
  {
    $article = new Article( $_POST['articleId'] );

    $errors = array();
    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";

    $article->setSectionId( $_POST['sectionId'] );

    // In case of multiple authors who can proofread, amend: don't update unless empty.
    if ( !$article->userId() )
      $article->setUserId( $user->id() );

    $article->setIpAddr( $_SERVER['REMOTE_ADDR'] );

    list( $date, $time ) = explode( " ", $_POST['ctime'] );
    list( $day, $month, $year ) = explode( "-", $date );
    $ctime = "$year-$month-$day $time";
    $article->setCtime( $ctime );

    if ( $_POST['cname'] && !count($article->publications()) )
    {
      // TODO: allow changing when publications exist: 301 redirect must be created somewhere in this case.
      $article->setCname( $_POST['cname'] );
    }

    $article->setTitle( $_POST['title'] );
    $article->setBody( $_POST['body'] );

    if ( isset($_POST['headerImage']) )
      $article->setHeaderImage( $_POST['headerImage'] );

    $article->setFeatured( (isset($_POST['featured']) && $_POST['featured']=='on') ? 1 : 0 );
    $article->setVisible( (isset($_POST['visible']) && $_POST['visible']=='on') ? 1 : 0 );
    $article->setHashtags( isset($_POST['hashtags']) ? $_POST['hashtags'] : null );
    $article->setAlbumId( isset($_POST['albumId']) ? $_POST['albumId'] : null );
        
    if ( !$article->body() )
      $errors[] = "Je kunt geen leeg artikel opslaan!";

    if ( !sizeof($errors) )
    {
      // Save article prior to publishing to create cname. 
      $article->save();
      
      // Publish article.
      if ( isset($_POST['connections']) )
      {
        foreach( $_POST['connections'] as $id => $value )
        {
          if ( $value != 'on' )
            continue;

          $connection = $user->getConnection($id);
          if ( $connection )
          {
            $rs = $connection->postArticle( $article );
            if ( isset($rs->id) )
            {
              $errors[] = "<p>". $connection->serviceName(). " status geupdate: <a target=\"_blank\" href=\"". $rs->url. "\">". $rs->url. "</a></p>\n";
            }
            else
              $errors[] = "<p>\nEr is een fout opgetreden bij het updaten van je ". $connection->serviceName(). " status:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
          }
        }

        // Update title of corresponding album
        $album = new Album( $article->albumId() );
        $album->setSystem(true);
        $album->setTitle( $article->title() );
        $album->save();              
      }
    }
    
    if ( isset($_POST['json']) )
    {
      $response = array();
      $response['formId'] = $_POST['formId'];
      $response['articleId'] = $article->id();
      $response['article'] = Articles::showSingle( $db, $user, $article->id(), true);
      $response['errors'] = $errors;

      header( 'Content-type: application/json' );
      echo json_encode( $response );
      exit();
    }

    if ( !count($errors) )
    {
      Router::redirect( $_SERVER['HTTP_REFERER'], 303 );
      exit();
    }

    $page = new AdminPage();
    $page->header();
    echo "fouten bij opslaan:";
    echo "<pre>";
    print_r($errors);
    echo "</pre>";
    $page->footer();
  }
?>