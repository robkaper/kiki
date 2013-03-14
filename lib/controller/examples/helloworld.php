<?php

class Controller_Examples_Helloworld extends Controller
{
  public function exec()
  {
		$this->title = "Hello world!";
		$this->status = 200;
		$this->template = 'pages/default';
		$this->content = "Hello world!";
  }
}
