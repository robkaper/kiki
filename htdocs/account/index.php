<?php

  $this->template = $user->isAdmin() ? 'pages/admin' : 'pages/default';
  $this->title = _("Your Account");

  $template = new Template( 'content/account-summary' );

  $this->content = $template->fetch();
