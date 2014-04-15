<?php

namespace Kiki;

class FlashBag
{
	public function __construct()
	{
		if ( !isset($_SESSION) )
			session_start();

		if ( !isset($_SESSION['kiki.flashBag']) || !is_array($_SESSION['kiki.flashBag']) )
		{
			\Kiki\Log::debug( "init flashbag session" );
			$_SESSION['kiki.flashbag'] = array();
		}
	}

	public function add( $type, $msg )
	{
		if ( !isset($_SESSION['kiki.flashBag'][$type]) )
			$_SESSION['kiki.flashBag'][$type] = array();

		$_SESSION['kiki.flashBag'][$type][] = $msg;
	}

	public function getData()
	{
		return $_SESSION['kiki.flashBag'];
	}

	public function reset()
	{
		unset($_SESSION['kiki.flashBag']);
	}

}
