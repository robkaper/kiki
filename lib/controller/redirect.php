<?php

namespace Kiki\Controller;

class Redirect extends \Kiki\Controller
{
  public function actionHandler()
  {
    $this->status = 301;
    $this->content = $this->action;
    return true;
  }
}
