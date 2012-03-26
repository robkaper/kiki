<?= Form::open( "createUserForm", Config::$kikiPrefix. "/account/create.php", 'POST' ); ?>
<?= Form::text( "email", $email, "E-mail" ); ?>
<?= Form::password( "password", $password, "Password" ); ?>
<?= Form::password( "password-repeat", $password, "Repeat password" ); ?>
<? if (!count(Config::$adminUsers)) echo Form::text( "password-admin", $adminPassword, "Administrator password<br /><span class=\"small\">This website has no administrator accounts. Enter the password from <tt>config.php</tt> to create this account as an administrator account.</span>" ); ?>
<?= Form::button( "submit", "submit", _("Create account") ); ?>
<?= Form::close(); ?>
