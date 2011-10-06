<?
  require_once "../../lib/init.php";

  $errors = array();

  if ( $_POST )
  {
    $email = $_POST['email'];
    $password = $_POST['password']; 

    $user->login( $email, $password );
    if ( !$user->id )
      $errors[] = "Invalid email/password combination";
  }

  $page = new Page( "Login" );
  $page->header();
  
  if ( $user->id )
  {
    echo "logged in as user ". $user->id. "\n";
  }
  else
  {
    if ( count($errors) )
      print_r($errors);

    echo "loginform\n";
  }
    echo "this ought to be a post..\n";

  $page->footer();
?>