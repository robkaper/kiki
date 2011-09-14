<?= Form::open( "createAdminForm", Config::$kikiPrefix. "/admin/create.php", 'POST' ); ?>
<?= Form::text( "email", $email, "E-mail" ); ?>
<?= Form::password( "password", $password, "Password" ); ?>
<?= Form::password( "password-repeat", $password, "Repeat password" ); ?>
<?= Form::button( "submit", "submit", "Create Admin Account" ); ?>
<?= Form::close(); ?>
