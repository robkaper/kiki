<?php

namespace Kiki\Controller;

use Kiki\Controller;

class HelloWorld extends Controller
{
  public function actionHandler()
  {
    $this->title = "Hello world!";
    $this->status = 200;
    $this->template = 'pages/default';
    $this->content = "Hello world!";
  }
}
