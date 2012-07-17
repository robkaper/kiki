<?
  $this->title = _("Create account") );

  ob_start();

  if ( $user->id() )
  {
    echo "<p>Logout first.</p>\n";
  }
  else if ( $_POST )
  {
    $errors = array();

    $email = $_POST['email'];
    $password = $_POST['password']; 
    $password2 = $_POST['password-repeat'];
    $adminPassword = isset($_POST['password-admin']) ? $_POST['password-admin'] : null;

    $userId = $db->getSingleValue( "select id from users where email='%s' limit 1", $email );
    if ( $userId )
      $errors[] = "An account with that e-mail address already exists. [Forgot your password?]";
    
    if ( !preg_match('/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) ) )
      $errors[] = "invalid email address";
    if ( !$password )
      $errors[] = "password cannot be empty";
    if ( $password != $password2 )
      $errors[] = "passwords don't match";

    $createAdmin = false;
    if ( $adminPassword )
    {
      if (Config::$adminPassword)
      {
        $createAdmin = ($adminPassword==Config::$adminPassword);
        if (!$createAdmin )
        {
          $errors[] = "admin password didn't match. leave empty to create a regular account";
        }
      }
    }
    
    if ( count($errors) )
      print_r( $errors );
    else
    {
      $user->storeNew( $email, $password, $createAdmin );

      // $user->reset();

      // FIXME: rjkcust, add validation
      /*
      $from = "Rob Kaper <rob@robkaper.nl>";
      $to = $email;
      $email = new Email( $from, $to, "Verify your ". $_SERVER['SERVER_NAME']. " account" );
      
      $msg = "Please verify your account:\n\n";
      $msg .= "http://robkaper.nl/kiki/account/verify.php?auth=". "abc123";
      $email->setPlain( $msg );
      Mailer::send($email);

      echo "<p>\nPlease check your e-mail and activate your account by opening the link within the verification message.</p>";
      */

      echo "<p>\nJe bent nu ingelogd.</p>";
      
    }
  }
  else
  {
    $adminsExist = count(Config::$adminUsers);
    
    $formTemplate = new Template('forms/user-create');
    $content = $formTemplate->fetch();
  }

  if ( !isset($content) )
    $content = ob_get_clean();
  else
    ob_end_clean();

  $this->content = $content;
