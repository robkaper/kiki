<?php

/**
 * Class providing basic account functionality.
 *
 * Can be installed as controller section under any URI, but has a fallback
 * to a Config::$kikiPrefix/account as fallback.
 *
 * @warning Some code and templates have the fallback hard-coded.
 * @todo Find hardcoded references in the code, replace with instancId based getLoginUrl methods
 * @todo Assign as templateData e.g. $account.loginUrl
 * @todo port assignment to generic Controller feature using $kiki.modules.account.loginUrl globally
 * @todo Use that template data
 *
 * @package Kiki
 * @author Rob Kaper <http://robkaper.nl/>
 * @copyright 2011-2013 Rob Kaper <http://robkaper.nl/>
 * @license Released under the terms of the MIT license.
 */

namespace Kiki\Controller;

class Account extends \Kiki\Controller
{
  public function exec()
	{
		\Kiki\Log::debug( "accountcontroller exec" );
		if ( $this->actionHandler() )
			return true;

		$this->subController = new Kiki();

		// No trailing slash: for htdocs/foo/bar.php
    $this->subController->setObjectId( "account/". $this->objectId );
		$result = $this->subController->fallback();
		if ( !$result )
		{
			// Trailing slash: for htdocs/foo/bar/index.php
	    $this->subController->setObjectId( "account/". $this->objectId. "/" );
			$result = $this->subController->fallback();
		}

		return $result;
	}

	public function getBaseUri( $action = null )
	{
		$uri = $this->instanceId ? \Kiki\Router::getBaseUri('account', $this->instanceId) : \Kiki\Config::$kikiPrefix. "/account";
		if ( !empty($action) )
		{
			if ( $uri[strlen($uri)-1] != '/' )
				$uri .= "/";

			$uri .= $action;
		}
		return $uri;
	}

	public function indexAction()
	{
		$user = \Kiki\Core::getUser();

		$this->template = $user->isAdmin() ? 'pages/admin' : 'pages/default';
		$this->status = 200;
		$this->title = _("Your Account");

		$template = new \Kiki\Template( 'content/account-summary' );
		$this->content = $template->fetch();

		return true;
	}

	public function loginAction()
	{
		// $this->template = 'pages/default';
		$this->status = 200;
		$this->title = _("Login");

		$errors = array();

		$user = \Kiki\Core::getUser();
		if ( !count($errors) )
		{
			$email = $_POST['email'] ?? null;
			$$password = $_POST['password'] ?? null; 
		}

		if ( $user->id() )
			\Kiki\Core::getFlashBag()->add( 'warning', _("You are already logged in.") );


		$userId = $user->getIdByLogin( $email, $password );
		if ( $userId )
		{
			$user->load($userId);

			if ( !$user->isVerified() )
			{
				$errors[] = "Your e-mail address has not been verified yet.";
			}
			else
			{
				\Kiki\Core::setUser($user);
				\Kiki\Auth::setCookie($userId);

				$this->status = 303;
				$this->content = $this->getBaseUri();
				return true;
			}
		}
		else
		{
			$errors[] = "Invalid email/password combination";
		}

		$template = new Template( 'content/account-login' );
		$template->assign( 'errors', $errors );

		$this->content = $template->fetch();

		return true;
	}

	public function logoutAction()
	{
		\Kiki\Auth::setCookie(0);

		$this->status = 302;
		$this->content = $this->getBaseUri('login');

		return true;
	}

	public function signupAction()
	{
	  $this->title = _("Create account");
		$this->status = 200;

		$template = new \Kiki\Template('content/account-create');
		$template->assign('postUrl', $this->getBaseUri('create') );

		$user = \Kiki\Core::getUser();

    $errors = array();

		if ( $user->id() )
		{
		}
	  else if ( $_POST )
	  {

	    $email = $_POST['email'];
			$template->assign('email', $email );
	    $password = $_POST['password'] ?? null; 

	   	$validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
			if ( !$validEmail )
	      $errors[] = _("You did not enter a valid e-mail address.");
	    if ( !$password )
	      $errors[] = _("Your password cannot be empty.");

			$createAdmin = false;
			if ( isset($adminPassword) )
    	{
      	if ( isset(\Kiki\Config::$adminPassword) )
      	{
        	$createAdmin = ($adminPassword==\Kiki\Config::$adminPassword);
        	if (!$createAdmin )
        	{
        	  $errors[] = "You did not enter the correct administration password. Try again, or leave it empty to create a regular account.";
        	}
      	}
    	}

			if ( !count($errors) )
			{
				$userId = $user->getIdByEmail( $email );
	  	  if ( $userId )
	  	    $errors[] = "An account with that e-mail address already exists. [Forgot your password?]";
			}
    
	    if ( !count($errors) )
			{
	      $user->storeNew( $email, $password, $createAdmin );
	
				if ( !$user->id() )
				{
					$errors[] = "Account created failed.";
				}
				else
				{
					$authToken = $user->getAuthToken();
		     	$user->reset();

					if ( !isset(\Kiki\Config::$smtpHost) )
					{
						$errors[] = "Your account was created, but we could not send the verification mail. Please contact <strong>". \Kiki\Config::$mailSender. "</strong>.";
					}
					else
					{
	  		    $from = \Kiki\Config::$mailSender;
	  		    $to = $email;
	  		    $email = new \Kiki\Email( $from, $to, "Verify your ". $_SERVER['SERVER_NAME']. " account" );
      
  	  		  $msg = "Please verify your account:\n\n";

						// Yes, send the actual auth token here. That's fine, because with
						// access to that e-mail address people could gain access to the
						// account anyway through the forget password mechanics.
						$url = sprintf( "http://%s%s?token=%s", $_SERVER['SERVER_NAME'], $this->getBaseUri('verify'), $authToken );
						$msg .= $url;

		      	$email->setPlain( $msg );
		      	\Kiki\Mailer::send($email);
					}

					$template->assign( 'accountCreated', true );
	    	}
			}
	  }
	  else
	  {
	    $adminsExist = count(\Kiki\Config::$adminUsers);

			$template->assign( 'adminsExist', $adminsExist );
	  }

		if ( count($errors) )
		{
			$template->assign('errors', $errors);
		}

	  $this->content = $template->fetch();

		return true;
	}

	public function verifyAction()
	{
		$this->status = 200;
		$this->template = 'pages/default';
		$this->title = _("Verify account");

		$template = new \Kiki\Template('content/account-verify');

		$errors = array();
		$warnings = array();

		$user = \Kiki\Core::getUser();

		$token = isset($_GET['token']) ? $_GET['token'] : null;
		if ( empty($token) )
		{
			$errors[] = "Auth token missing.";
		}
		else
		{
			// Get user by auth token.
			$verifyUserId = $user->getIdByToken( $token );
			if ( !$verifyUserId )
			{
				$errors[] = "Invalid auth token. Auth tokens expire. [Send new verification e-mail]";
			}
			else
			{
				$verifyUser = new \Kiki\User($verifyUserId);
				$verifyUser->setIsVerified(true);
				$verifyUser->save();

				if ( $user->id() && $user->id() != $verifyUser->id() )
				{
					$warnings[] = sprintf( "Because you verified account <strong>%s</strong> (%d), you are no longer logged in as <strong>%s</strong> (%d).", $verifyUser->email(), $verifyUser->id(), $user->email(), $user->id() );
				}
				else
				{
					Auth::setCookie( $verifyUser->id() );
					$user = $verifyUser;

					\Kiki\Core::setUser($verifyUser);
					$mainTemplate = \Kiki\Template::getInstance();
					$mainTemplate->assign('user', $user->templateData() );
				}
			}
		}

		$template->assign('warnings', $warnings);
		$template->assign('errors', $errors);
	  $this->content = $template->fetch();

		return true;
	}

}
