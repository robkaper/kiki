<?php

namespace Kiki\Controller;

use Kiki\Core;
use Kiki\Log;

class Kiki extends \Kiki\Controller
{
  public function exec()
  {
		if ( $this->actionHandler() )
			return;

		$this->fallback();
	}

	public function fallback()
	{
	  $parts = parse_url($this->objectId);
	  if ( !isset($parts['path']) )
	    return false;

		$kikiFile = Core::getInstallPath(). "/htdocs/". $parts['path'];
   	if ( file_exists($kikiFile) )
	  {
      $ext = \Kiki\Storage::getExtension($kikiFile);
      switch($ext)
      {
        case 'css':
        case 'gif':
        case 'jpg':
        case 'js':
        case 'png':

          $this->altContentType = \Kiki\Storage::getMimeType($ext);
          $this->template = null;
          $this->status = 200;
          $this->content = file_get_contents($kikiFile);

 	        return true;
 	        break;

 	      case 'php':

 	        Log::debug( "Controller_Kiki: PHP file $kikiFile" );

 	        $this->status = 200;
 	        $this->template = 'pages/default';

   	      $user = Core::getUser();
   	      $db = Core::getDb();

     	    include_once($kikiFile);

     	    return true;
     	    break;

 	      case '':

 	        if ( file_exists($kikiFile. "index.php") )
 	        {
 	          Log::debug( "Controller_Kiki: PHP index file $kikiFile". "index.php" );

   	        $this->status = 200;
   	        $this->template = 'pages/default';

   	        $user = Core::getUser();
   	        $db = Core::getDb();

   	        include_once($kikiFile. "index.php");

   	        return true;
   	      }
     	    break;

   	   default:;

   	 }
   	 Log::debug( "unsupported extension $ext for kiki htdocs file $kikiFile" );
   	}
   	else
		{
   	  Log::debug( "non-existing kikiFile $kikiFile" );
	 	}

		return false;
	}

	// FIXME: grant, revoke, redirect actions from connectionservices should be delegated to their own controller (part of a, yikes, "Bundle" perhaps)

	/**
	 * Redirects a user to the Facebook auth dialog after clearing permissions
	 * so they will be reverified upon next hasPerm() call.
	 */
	public function facebookGrantAction()
	{
		if ( isset($_GET['id']) && isset($_GET['permission']) )
		{
			foreach( $user->connections() as $connection )
			{
				if ( $connection->serviceName() == 'Facebook' && $connection->id() == $_GET['id'] )
				{
					$connection->clearPermissions();
					$this->status = 302;
					$this->content = $connection->getLoginUrl( array( 'scope' => $_GET['permission'], 'redirect_uri' => $_SERVER['HTTP_REFERER'] ), true );
					return false;
				}
			}
		}

	  $this->status = 302;
		$this->content = $_SERVER['HTTP_REFERER'];

		return true;
	}

	/**
	 * Revokes a Facebook permission and redirects to referer.
	 */
	public function facebookRevokeAction()
	{
	  if ( isset($_GET['id']) && isset($_GET['permission']) )
	  {
	    foreach( $user->connections() as $connection )
	    {
	      if ( $connection->serviceName() == 'Facebook' && $connection->id() == $_GET['id'] )
	      {
	        $connection->revokePerm( $_GET['permission'], true );
	      }
	    }
	  }

	  $this->status = 302;
		$this->content = $_SERVER['HTTP_REFERER'];

		return true;
	}

	/**
	 * Builds a Twitter authorisation URL using the generic application token
	 * and referer as callback URL, then redirects to it.
	 */
	public function twitterRedirectAction()
	{
		if ( isset(\Kiki\Config::$twitterOAuthPath) )
		{
			require_once \Kiki\Config::$twitterOAuthPath. "/twitteroauth/twitteroauth.php";
		}
		else
		{
			$this->content = _("Error in Twitter configuration (TwitterOAuth path not set).");
			Log::debug( "SNH: called without Twitter configuration" );
			return false;
		}

		// Build TwitterOAuth object with app credentials.
		$connection = new \TwitterOAuth( \Kiki\Config::$twitterApp, \Kiki\Config::$twitterSecret );
 
		// Get temporary credentials. Even though a callback URL is specified
		// here, it must be set in your Twitter application settings as well to
		// avoid a PIN request (it can be anything actually, just not empty).
		$callbackUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		$requestToken = $connection->getRequestToken( $callbackUrl );

		// Save temporary credentials to session.
		$_SESSION['oauth_token'] = $token = $requestToken['oauth_token'];
		$_SESSION['oauth_token_secret'] = $requestToken['oauth_token_secret'];

		switch ($connection->http_code)
		{
			case 200:
				// Build authorize URL and redirect user to Twitter.
				$this->status = 302;
				$this->content = $connection->getAuthorizeURL($token);
				break;

			default:
				$this->content = _("Could not connect to Twitter. Try again later.");
		}

		return true;
	}

	public function accountAction( $objectId )
	{
		$this->subController = new Account();
		$this->subController->setObjectId( $objectId );

		$result = $this->subController->exec();
		if ( !$result )
			unset($this->subController);

		return $result;
	}

}
