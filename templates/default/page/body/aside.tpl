<div id="sw"><aside>
<?
  if ( $user->id() )
  {
    $connectedServices = array();
    foreach( $user->connections() as $connection )
    {
      $connectedServices[] = $connection->serviceName();
      include Template::file( 'parts/connections/account-box' );
    }
    foreach( Config::$connectionServices as $name )
    {
      if ( !in_array( $name, $connectedServices ) )
      {
        $service = Factory_ConnectionService::getInstance($name);
        include Template::file( 'parts/connectionservices/connect-box' );
      }
    }
?>
<div class="box">
<?= Boilerplate::accountLinks(); ?>
</div>
<?
  }
  else
  {
?>
<div id="login" class="box">
<?
    foreach( Config::$connectionServices as $name )
    {
      $service = Factory_ConnectionService::getInstance($name);
      include Template::file( 'parts/connectionservices/login-box' );
    }
    include Template::file( 'parts/newaccount-box' );
?>
</div>
<?
  }
?>
<div class="box">
<?
  // FIXME: make conditional based on Config::privacyUrl or something similar, even though I
  // think every site should have a proclaimer and privacy policy...
  echo "<p><a href=\"/proclaimer.php#privacy\">Privacybeleid</a></p>\n";

  // FIXME: rjkcust
  if ( 0 && $user->isAdmin() )
    Google::adSense( "4246395131" );
?>
</div>
</aside></div>