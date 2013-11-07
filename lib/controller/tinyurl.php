<?php

/**
 * Controller for TinyURL resources.
 * 
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki\Controller;

use Kiki\Controller;

class TinyUrl extends Controller
{
  public function exec()
  {
    $uri = \Kiki\TinyUrl::lookup62($this->objectId);
    if ( $this->content = $uri )
      $this->status = 301;
  }
}
  
?>