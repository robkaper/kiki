<?php

  $this->title = "phpinfo()";

  if ( !$user->isAdmin() )
  {
    $this->template = 'pages/admin-required';
    return;
  }

  $this->template = 'pages/admin';

  ob_start();

  if ( $user->isAdmin() )
    phpinfo();
  else
  {
?>
<p>
Leuk geprobeerd, maar alleen ontwikkelaars van deze site hebben toegang tot <tt>phpinfo()</tt> informatie.</p>
<?php
  }

  $content = ob_get_clean();
  $content = preg_replace( '~h2>~', 'h3>', $content );
  $content = preg_replace( '~h1( class="p")?>~', 'h2>', $content );
  $this->content = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1', $content );
?>