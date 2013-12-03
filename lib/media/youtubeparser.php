<?php

namespace Kiki\Media;

use Kiki\Template;

class YouTubeParser
{
	private $code = null;

	public function __construct( $code = null )
	{
		$this->code = $code;
	}

	public function setCode( $code )	
	{
		$this->code = $code;
	}

	public function parseHtml( $html )
	{
		$matches = array();
		preg_match( '#(\.be/|/embed/|/v/|/watch\?v=)([A-Za-z0-9_-]{5,11})#', $html, $matches );

		$this->code = isset($matches[2]) ? $matches[2] : null;
	}

	public function getHtml( $forceHtml5 = false )
	{
		$template = new Template('parts/google/youtube');
		$template->assign( 'youtubeCode', $this->code );
		$template->assign( 'forceHtml5', $forceHtml5 );
		return $template->fetch();
	}
}
