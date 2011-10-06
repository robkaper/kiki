<?
  require_once "../../lib/init.php";

  Auth::setCookie(0);
  Router::redirect( "/kiki/account/", false );
?>
