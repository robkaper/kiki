<?
  include_once "../lib/init.php";

  // @todo this entire file ought to be deprecated for router.php which can
  // include 404 template as last resort while also handling all the
  // redirects (database driven)

  // @todo remove rjkcust
  $redirectUrl = null;
  switch ( $_SERVER['REQUEST_URI'] )
  {
    case "/adres-routebeschrijving.php":
    case "/routebeschrijving.php":
      $redirectUrl = "/contact/";
      break;
    case "/webdev/kiki-todo.php":
      $redirectUrl = "/webdev/kiki/todo.php";
      break;
    case "/juppihippipunkkarob/de-wederopstand-van-mijn-blog":
      $redirectUrl = "/juppihippipunkkarob/de-wederopstanding-van-mijn-blog";
      break;
    case "/lowlands/":
      $redirectUrl = "/sziget/";
      break;
    case "/lowlands/geen-lowlands-dit-jaar":
      $redirectUrl = "/sziget/geen-lowlands-dit-jaar";
      break;
    case "/lowlands/het-alternatief-voor-lowlands-sziget":
      $redirectUrl = "/sziget/het-alternatief-voor-lowlands-sziget";
      break;
    case "/admin/social.php":
      $redirectUrl = "/kiki/account/social.php";
      break;
    default:
      $redirectUrl = TinyUrl::lookup62( substr( $_SERVER['REQUEST_URI'], 1 ) );
      break;
  }

  if ( $redirectUrl )
  {
    Log::debug( "404-redirect: ". $redirectUrl );
    header( "Location: $redirectUrl", true, 301 );
    exit();
  }

  $page = new Page( "404 Not Found", "Is dat even jammer!" );
  $page->setHttpStatus(404);
  $page->header();
?>

<p>
Tja, dat is pech hebben. Deze pagina bestaat dus (niet) meer.</p>

<?
  $page->footer();
?>
