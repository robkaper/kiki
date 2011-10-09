<?
/**
 * Handles creation and modification requests (POSTS) for articles.
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2010-2011 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

  require_once "../../lib/init.php";

  if ($_POST)
  {
    $article = new Article( $_POST['articleId'] );

    $errors = array();
    if ( !$user->id() )
      $errors[] = "Je bent niet ingelogd.";

    $article->setSectionId( $_POST['sectionId'] );

    // FIXME: only set it, don't update if use id exists in case of multiple
    // authors who can amend/edit/proofread each other's work.
    $article->setUserId( $user->id() );
    $article->setIpAddr( $_SERVER['REMOTE_ADDR'] );

    list( $date, $time ) = explode( " ", $_POST['ctime'] );
    list( $day, $month, $year ) = explode( "-", $date );
    $ctime = "$year-$month-$day $time";
    $article->setCtime( $ctime );

    $article->setTitle( $_POST['title'] );
    $article->setBody( $_POST['body'] );

    if ( isset($_FILES['headerImage']) )
    {
      $tmpFile = $_FILES['headerImage']['tmp_name'];
      $name = $_FILES['headerImage']['name'];
      $size = $_FILES['headerImage']['size'];

      $storageId = $tmpFile ? Storage::save( $name, file_get_contents($tmpFile) ) : 0;
      if ( $storageId )
        $article->setHeaderImage( $storageId );
    }

    $article->setFeatured( (isset($_POST['featured']) && $_POST['featured']=='on') ? 1 : 0 );
    $article->setVisible( (isset($_POST['visible']) && $_POST['visible']=='on') ? 1 : 0 );
    $article->setFacebookUrl( $_POST['facebookUrl'] );
    $article->setTwitterUrl( $_POST['twitterUrl'] );
    
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
              if ( $connection->serviceName() == 'Facebook' )
                $article->setFacebookUrl( $rs->url );
              else if ( $connection->serviceName() == 'Twitter' )
                $article->setTwitterUrl( $rs->url );
            }
            else
              $errors[] = "<p>\nEr is een fout opgetreden bij het updaten van je ". $connection->serviceName(). " status:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
          }
        }

        // Save article again, to store connection URLs.        
        $article->save();
              
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

    Router::redirect( $_SERVER['HTTP_REFERER'], 303 );
  }
?>