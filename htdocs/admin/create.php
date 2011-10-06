<?
  require_once "../../lib/init.php";

  $page = new Page( "Create Admin account" );
  $page->header();

  $adminsExist = count(Config::$adminUsers);
  $errors = array();
  if ( $adminsExist )
    $errors[] = "admin account already exists..\n";
  else if ( $_POST )
  {
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $password2 = $_POST['password-repeat'];

    if ( !preg_match('/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) ) )
      $errors[] = "invalid email address";
    if ( !$password )
      $errors[] = "password cannot be empty";
    if ( $password != $password2 )
      $errors[] = "passwords don't match";
    
    if ( count($errors) )
      print_r( $errors );
    else
    {
      $user->storeNew( $email, $password, true );
    }
  }
  else
    echo "this ought to be a post..\n";

  print_r( $user );

  $page->footer();
?>