<?

/**
* @file htdocs/json/article.php
* Handles Ajax saves of Article forms.
* @author Rob Kaper <http://robkaper.nl/>
* @section license_sec License
* Released under the terms of the MIT license.
*/
  include_once "../../lib/init.php";

  if ($_POST)
  {
    $article = new Article( $_POST['articleId'] );

    $errors = array();
    if ( !$user->id )
      $errors[] = "Je bent niet ingelogd.";

    $article->setSectionId( $_POST['sectionId'] );

    // @fixme only set it, don't update if use id exists in case of multiple
    // authors who can amend/edit/proofread each other's work.
    $article->setUserId( $user->id() );
    $article->setIpAddr( $_SERVER['REMOTE_ADDR'] );

    list( $date, $time ) = explode( " ", $_POST['ctime'] );
    list( $day, $month, $year ) = explode( "-", $date );
    $ctime = "$year-$month-$day $time";
    $article->setCtime( $ctime );

    $article->setTitle( $_POST['title'] );
    $article->setBody( $_POST['body'] );
    $article->setVisible( (isset($_POST['visible']) && $_POST['visible']=='on') ? 1 : 0 );
    $article->setFacebookUrl( $_POST['facebookUrl'] );
    $article->setTwitterUrl( $_POST['twitterUrl'] );
    
    if ( !$article->body() )
      $errors[] = "Je kunt geen leeg artikel opslaan!";

    if ( !sizeof($errors) )
    {
      // Publish article.
      foreach( $_POST['connections'] as $id => $value )
      {
        if ( $value != 'on' )
          continue;

        $connection = $user->getConnection($id);
        if ( $connection )
        {
          $rs = $connection->postArticle( $user, $this );
          if ( isset($rs->id) )
          {
            echo "<p>". $connection->serviceName(). " status geupdate: <a target=\"_blank\" href=\"". $rs->url. "\">". $rs->url. "</a></p>\n";
          }
          else
            echo "<p>\nEr is een fout opgetreden bij het updaten van je ". $connection->serviceName(). " status:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
        }
      }
    }

    // @fixme twitter/facebook URLs are not being saved currently
    $article->save();
    
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

    header( 'Location: '. $_SERVER['HTTP_REFERER'], true, 301 );
  }
?>