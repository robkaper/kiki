<?
  // TODO: finalise error handling for twitter post with insufficient permissions
  // TODO: add follow me/friend me buttons/links to external social sites
  // FIXME: make jsonable
  // TODO: error handling when message empty or no social network selected (requires: form validation)

  $template = Template::getInstance();

  if ( !$user->id() )
  {
    $template->load( 'pages/account-required' );
    echo $template->content();
    exit();
  }

  $template->load( $user->isAdmin() ? 'pages/admin' : 'pages/default' );
  
  $template->assign( 'title', _("Social update") );

  ob_start();
    
  if ( $_POST )
  {
    if ( $msg = $_POST['msg'] )
    {
      $update = new SocialUpdate();
      $update->save();

      foreach( $_POST['connections'] as $id => $value )
      {
        if ( $value != 'on' )
         continue;

        $connection = $user->getConnection($id);
        if ( $connection )
        {
          $rs = $connection->post( $update->objectId(), $msg );
          if ( isset($rs->id) )
            echo "<p>". $connection->serviceName(). " status geupdate: <a target=\"_blank\" href=\"". $rs->url. "\">". $rs->url. "</a></p>\n";
          else if ( $rs->error == 'Read-only application cannot POST' )
          {
            // FIXME: temporary, either make app RW from the start or have two apps (one RO, one RW)
            echo "<p>\nJe hebt deze site alleen leesrechten gegeven en geen schrijfrechten. Helaas laat Twitter je deze rechten niet eenvoudig uitbreiden, je moet hiervoor twee stappen ondernemen:</p>\n";
            echo "<ol>\n";
            echo "<li>Verwijder de toegang van <b>robkaper.nl</b> bij je <a target=\"_blank\" href=\"http://twitter.com/settings/connections\">Twitter connection settings</a> (<q>Revoke access</q>)</li>\n";
            echo "<li><a href=\"/twitter-redirect.php\">Log opnieuw in</a>. Twitter geeft deze site dan lees- en schrijfrechten.</li>\n";
            echo "</ol>\n";
          }
          else
            echo "<p>\nEr is een fout opgetreden bij het updaten van je ". $connection->serviceName(). " status:</p>\n<pre>". print_r( $rs->error, true ). "</pre>\n";
        }
      }
    }
    else
      echo "<p>\nJe kunt geen lege status versturen.</p>\n";
  }

  if ( $user->anyUser() )
  {
    echo Form::open( "socialForm" );
    echo Form::textarea( "msg", null, "Message", "Waar denk je aan?", 140 );
    foreach ( $user->connections() as $connection )
    {
      if ( $connection->serviceName() == 'Facebook' )
      {
        // TODO: inform user that, and why, these are required.
        if ( !$connection->hasPerm('publish_stream') )
         continue;
      }
      echo Form::checkbox( "connections[". $connection->uniqId(). "]", false, $connection->serviceName(), $connection->name() );
    }

    echo Form::button( "submit", "submit", "Update status" );
    echo Form::close();
  }
  else
    echo Boilerplate::login();

  $template->assign( 'content', ob_get_clean() );
  echo $template->content();
?>