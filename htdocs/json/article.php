<?php

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

    if ( isset($_POST['ctime']) )
    {
      list( $date, $time ) = explode( " ", $_POST['ctime'] );
      list( $day, $month, $year ) = explode( "-", $date );
      $ctime = "$year-$month-$day $time";
      $article->setCtime( $ctime );
    }
    else
      $showAsPage = true;

    if ( isset($_POST['cname']) && !count($article->publications()) )
    {
      // TODO: allow changing when publications exist: 301 redirect must be created somewhere in this case.
      $article->setCname( $_POST['cname'] );
    }

    $article->setTitle( $_POST['title'] );
    $article->setBody( $_POST['body'] );

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
      
      if ( $showAsPage )
      {
        $template = new Template( 'content/pages-single' );
        $template->assign( 'page', $article->templateData() );
      }
      else
      {
        $template = new Template( 'content/articles-single' );
        $template->assign( 'article', $article->templateData() );
      }
      $response['article'] = $template->fetch();

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

    $template = Template::getInstance();
    $template->load( 'pages/admin' );

    $template->assign( 'content',  "fouten bij opslaan:<pre>". print_r($errors,true). "</pre>" );
    echo $template->content();
  }
?>