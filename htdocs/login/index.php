<?
  $this->title = _("Login");

  ob_start();

  $errors = array();

  if ( $_POST )
  {
    $email = $_POST['email'];
    $password = $_POST['password']; 

    $user->login( $email, $password );
    if ( !$user->id )
      $errors[] = "Invalid email/password combination";
  }

  if ( $user->id() )
  {
    echo "logged in as user ". $user->id(). "\n";
  }
  else
  {
    if ( count($errors) )
      print_r($errors);

    echo "loginform\n";
  }
    echo "this ought to be a post..\n";

  $this->content = ob_get_clean();
?>