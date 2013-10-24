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

class Controller_Account extends Controller
{
  public function exec()
	{
		if ( $this->actionHandler() )
			return true;

		$this->subController = new Controller_Kiki();
    $this->subController->setObjectId( "account/". $this->objectId. "/" );

    return $this->subController->fallback();
	}

	public function getBaseUri( $action = null )
	{
		$uri = $this->instanceId ? Router::getBaseUri('account', $this->instanceId) : Config::$kikiPrefix. "/account";
		if ( !empty($action) )
			$uri .= "/$action";

		return $uri;
	}

	public function indexAction()
	{
		$user = Kiki::getUser();

		$this->template = $user->isAdmin() ? 'pages/admin' : 'pages/default';
		$this->status = 200;
		$this->title = _("Your Account");

		$template = new Template( 'content/account-summary' );
		$this->content = $template->fetch();

		return true;
	}

	public function loginAction()
	{
		// $this->template = 'pages/default';
		$this->status = 200;
		$this->title = _("Login");

		$errors = array();

		$user = Kiki::getUser();

		if ( $user->id() )
			$errors[] = _("You are already logged in.");

		if ( !count($errors) && $_POST )
		{
	    $email = $_POST['email'];
	    $password = $_POST['password']; 

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
					Kiki::setUser($user);
					Auth::setCookie($userId);

					$this->status = 303;
			    $this->content = $this->getBaseUri();
					return true;
				}
			}
			else
			{
  	    $errors[] = "Invalid email/password combination";
			}
		}

		$template = new Template( 'content/account-login' );
		$template->assign( 'errors', $errors );

		$this->content = $template->fetch();

		return true;
	}

	public function logoutAction()
	{
		Auth::setCookie(0);

		$this->status = 302;
		$this->content = $this->getBaseUri('login');

		return true;
	}

	public function createAction()
	{
	  $this->title = _("Create account");

		$template = new Template('content/account-create');
		$template->assign('postUrl', $this->getBaseUri('create') );

		if ( $user->id() ) {}
	  else if ( $_POST )
	  {
	    $errors = array();

	    $email = $_POST['email'];
			$template->assign('email', $email );
	    $password = $_POST['password']; 
	    $password2 = $_POST['password-repeat'];
	    $adminPassword = isset($_POST['password-admin']) ? $_POST['password-admin'] : null;

	   	$validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
			if ( !$validEmail )
	      $errors[] = _("You did not enter a valid e-mail address.");
	    if ( !$password )
	      $errors[] = _("Your password cannot be empty.");
	    if ( $password != $password2 )
	      $errors[] = _("The passwords entered did not match.");

			$createAdmin = false;
			if ( isset($adminPassword) )
    	{
      	if ( isset(Config::$adminPassword) )
      	{
        	$createAdmin = ($adminPassword==Config::$adminPassword);
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

  		    // FIXME: rjkcust, add validation
  		    $from = "Rob Kaper <rob@robkaper.nl>";
  		    $to = $email;
  		    $email = new Email( $from, $to, "Verify your ". $_SERVER['SERVER_NAME']. " account" );
      
    		  $msg = "Please verify your account:\n\n";

					// Yes, send the actual auth token here. That's fine, because with
					// access to that e-mail address people could gain access to the
					// account anyway through the forget password mechanics.
					$url = sprintf( "http://%s%s?token=%s", $_SERVER['SERVER_NAME'], $this->getBaseUri('verify'), $authToken );
					$msg .= $url;

	      	$email->setPlain( $msg );
	      	Mailer::send($email);

					$template->assign( 'accountCreated', true );
	    	}
			}
	  }
	  else
	  {
	    $adminsExist = count(Config::$adminUsers);

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

		$template = new Template('content/account-verify');

		$errors = array();
		$warnings = array();

		$user = Kiki::getUser();

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
				$verifyUser = new User($verifyUserId);
				$verifyUser->setIsVerified(true);
				$verifyUser->save();

				if ( $user->id() )
				{
					$warnings[] = sprintf( "Because you verified account <strong>%s</strong> (%d), you are no longer logged in as <strong>%s</strong> (%d).", $verifyUser->email(), $verifyUser->id(), $user->email(), $user->id() );
				}
				else
				{
					Auth::setCookie( $verifyUser->id() );
					$user = $verifyUser;

					Kiki::setUser($verifyUser);
					$mainTemplate = Template::getInstance();
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
