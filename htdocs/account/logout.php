<?php

  Auth::setCookie(0);
  Router::redirect( Config::$kikiPrefix. "/account/" ) && exit();
?>