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

use Kiki\Core;
use Kiki\Auth;

use Kiki\Router;

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

  public function loginAction( $cookieConsent = false )
  {
    $this->template = 'pages/login';
    $this->status = 200;
    $this->title = _("Login");

    $user = \Kiki\Core::getUser();
    if ( !count($this->errors) )
    {
      $email = $_POST['email'] ?? null;
      $password = $_POST['password'] ?? null; 
    }

    if ( $user->id() )
    {
      $this->warnings[] = array( 'msg' => "You are already logged in." );
      \Kiki\Core::getFlashBag()->add( 'warning', _("You are already logged in.") );
    }

    $userId = $user->getIdByLogin( $email, $password );
    if ( $userId )
    {
      $user->load($userId);

      if ( !$user->isVerified() )
      {
        $this->errors[] = array( 'msg' => "Your e-mail address has not been verified yet." );
      }
      else
      {
        \Kiki\Core::setUser($user);
        \Kiki\Auth::setCookie($userId, $cookieConsent );

        // FIXME: this is rather specific. But local namespaced class can override it...
        $this->status = 303;
        $this->content = '/'; // $this->getBaseUri();
        return true;
      }
    }
    else if ( $_POST )
    {
      $this->errors[] = array( 'msg' => "Invalid email/password combination" );
    }

    return true;
  }

  public function logoutAction()
  {
    \Kiki\Auth::setCookie(0);

    $this->status = 303;
    $this->content = '/login'; // $this->getBaseUri('login');

    return true;
  }

  public function request_password_resetAction()
  {
    if ( !isset(\Kiki\Config::$smtpHost) )
      $this->errors[] = array( 'msg' => "This website does not have the correct settings configured to send e-mail. Please contact <strong>". \Kiki\Config::$mailSender. "</strong>." );

    if ( !$_POST )
      return true;

    $user = Core::getUser();

    $email = $_POST['email'] ?? null;

    $validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
    if ( !$validEmail )
      $this->errors[] = array( 'msg' => _("You did not enter a valid e-mail address.") );

    $this->data['emailSent'] = true;

    if ( count($this->errors) )
      return true;

    // Always say something was sent, don't disclose existance of account/e-mail
    $this->notices[] = array( 'msg' => "An e-mail with a link to reset your password has been sent to <strong>". htmlspecialchars($email). "</strong>." );
    $this->data['emailSent'] = true;

    $userId = $user->getIdByEmail($email);
    if ( !$userId )
      return true;

    $user->load( $userId );
    $authToken = $user->getAuthToken();
    $user->reset();

    $from = \Kiki\Config::$mailSender;
    $url = sprintf( "https://%s%s?token=%s", $_SERVER['SERVER_NAME'], Router::getBaseUri( 'Account', 'reset_password' ), $authToken );

    $mail = new \Kiki\Email( $from, $email, "Password reset request for your ". $_SERVER['SERVER_NAME']. " account" );

    $template = new \Kiki\Template( 'email/request-password-reset', true );

    $template->assign( 'url', $url );
    $html = $template->content( false );

    if ( $html )
      $mail->setHtml( $html );
    else
      $mail->setPlain( strip_tags($html) );

    $rs = \Kiki\Mailer::send($mail);

    return true;
  }

  public function reset_passwordAction()
  {
    $token = $_REQUEST['token'] ?? null;
    $this->data['token'] = $token;

    $user = Core::getUser();
    $userId = $user->getIdByToken($token);

    if ( !$userId )
      $this->errors[] = array( 'msg' => 'Invalid or expired token.' );

    $user->load($userId);

    $this->data['email'] = $user->email();

    if ( $_POST && !count($this->errors) )
    {
      $user->setAuthToken( Auth::hashPassword( $_POST['password'] ) );
      $user->save();

      $this->data['resetSuccessful'] = true;

      $this->notices[] = array( 'msg' => 'Password saved!' );
    }

    $user->reset();

    return true;
  }

  public function signupAction()
  {
    $this->title = _("Create account");
    $this->status = 200;

    $template = new \Kiki\Template('content/account-create');
    $template->assign('postUrl', $this->getBaseUri('create') );

    $user = \Kiki\Core::getUser();

    if ( $user->id() )
    {
      $this->warnings[] = array( 'msg' => "You are already logged in." );
      \Kiki\Core::getFlashBag()->add( 'warning', _("You are already logged in.") );
    }
    else if ( $_POST )
    {

      $email = $_POST['email'] ?? null;
      $template->assign('email', $email );
      $password = $_POST['password'] ?? null; 

      $validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
      if ( !$validEmail )
        $this->errors[] = array( 'msg' => _("You did not enter a valid e-mail address.") );
      if ( !$password )
        $this->errors[] = array( 'msg' => _("Your password cannot be empty.") );

      $createAdmin = false;
      if ( isset($adminPassword) )
      {
        if ( isset(\Kiki\Config::$adminPassword) )
        {
          $createAdmin = ($adminPassword==\Kiki\Config::$adminPassword);
          if (!$createAdmin )
          {
            $this->errors[] = array( 'msg' => "You did not enter the correct administration password. Try again, or leave it empty to create a regular account." );
          }
        }
      }

      if ( !count($this->errors) )
      {
        $userId = $user->getIdByEmail( $email );
        // Disable to send email despite existing account
        if ( $userId )
          $this->errors[] = array( 'msg' => "An account with that e-mail address already exists. [Forgot your password?]" );
      }
    
      if ( !count($this->errors) )
      {
        $user->storeNew( $email, $password, $createAdmin );

        // Disable to send email despite existing account
        // if ( !$user->id() )
         //  $user->setId( $userId );
  
        if ( !$user->id() )
        {
          $this->errors[] = array( 'msg' => "Account created failed." );
        }
        else
        {
          $authToken = $user->getAuthToken();
          $user->reset();

          if ( !isset(\Kiki\Config::$smtpHost) )
          {
            $this->errors[] = array( 'msg' => "Your account was created, but we could not send the verification mail. Please contact <strong>". \Kiki\Config::$mailSender. "</strong>." );
          }
          else
          {
            $from = \Kiki\Config::$mailSender;
            $url = sprintf( "https://%s%s?token=%s", $_SERVER['SERVER_NAME'], Router::getBaseUri( 'Account', 'verify' ), $authToken );

            $mail = new \Kiki\Email( $from, $email, "Verify your ". $_SERVER['SERVER_NAME']. " account" );

            $template = new \Kiki\Template( 'email/signup', true );

            $template->assign( 'url', $url );
            $html = $template->content( false );

            if ( $html )
              $mail->setHtml( $html );
            else
              $mail->setPlain( strip_tags($html) );

            $rs = \Kiki\Mailer::send($mail);
            
            $this->notices[] = array( 'msg' => "Your account was succesfully created. A verification e-mail has been sent to <strong>". htmlspecialchars($email). "</strong>, you will be able to log in once your e-mail address has been verified." );
          }

          $template->assign( 'accountCreated', true );
        }
      }
    }

    if ( count($this->errors) )
    {
      $template->assign('errors', $this->errors);
    }

    // TODO: don't really need local template anymore now that notices, warnings and errors are handled from main template
    return true;

    $this->content = $template->fetch();

    return true;
  }

  public function verifyAction()
  {
    $this->status = 200;
    $this->template = 'pages/login';
    $this->title = _("Verify account");

    $user = \Kiki\Core::getUser();

    $token = isset($_GET['token']) ? $_GET['token'] : null;
    if ( empty($token) )
    {
      $this->errors[] = array( 'msg' => "E-mail address verification token missing." );
    }
    else
    {
      // Get user by auth token.
      $verifyUserId = $user->getIdByToken( $token );
      if ( !$verifyUserId )
      {
        // TODO: offer way to send new verification e-mail
        $this->errors[] = array( 'msg' => "Invalid e-mail address verification token. These tokens expire." );
      }
      else
      {
        $verifyUser = new \Kiki\User($verifyUserId);
        $verifyUser->setIsVerified(true);
        $verifyUser->save();

        if ( $user->id() && $user->id() != $verifyUser->id() )
        {
          Auth::setCookie(0);

          $this->warnings[] = array( 'msg' => sprintf( "Because you verified account <strong>%s</strong> (%d), you are no longer logged in as <strong>%s</strong> (%d).", $verifyUser->email(), $verifyUser->id(), $user->email(), $user->id() ) );
        }
        else
        {
          // Disabled, users should still login after e-mail verification.
          // \Kiki\Auth::setCookie( $verifyUser->id() );
          // $user = $verifyUser;
          // \Kiki\Core::setUser($verifyUser);
          $this->notices[] = array( 'msg' => "Your e-mail address was succesfully verified. You can now log in." );

          $mainTemplate = \Kiki\Template::getInstance();
          $mainTemplate->assign('user', $user->templateData() );
        }
      }
    }

    return true;
  }
}
