<?= Form::open( "loginForm", Config::$kikiPrefix. "/login/", 'POST' ); ?>
<?= Form::text( "email", $email, "E-mail" ); ?>
<?= Form::password( "password", $password, "Password" ); ?>
<?= Form::button( "submit", "submit", "Login" ); ?>
<?= Form::close(); ?>
