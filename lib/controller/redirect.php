<?php

namespace Kiki\Controller;

class Redirect extends \Kiki\Controller
{
  public function exec()
  {
    $this->status = 301;
    $this->content = $this->action;
    return true;
  }
}
