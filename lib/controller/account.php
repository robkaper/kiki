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
use Kiki\Log;

class Account extends \Kiki\Controller
{
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
    $this->title = _("Log in");

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

    // Kiki does not offer a full MFA implementation itself, but provides
    // the necessary flow for class extensions to do so:

    // For users with MFA enabled the object metadata key 'mfa_enabled'
    // should be set to a value considered TRUE

    // After providing their login credentials, Kiki sets the key 'mfaForm'
    // to true in the data storage of this Controller, as well as a session
    // variable mfaUserId with the User id, instead of relaying the User to
    // the Kiki Core class and setting the authentication cookie.

    // Implementations should call parent::loginAction() in their
    // loginAction. If afterwards the session variable mfaUserId is set,
    // implementations should display their MFA form and/or handle its POST
    // data to verify the entered token.

    // Upon success, implementations should clear the session variable and
    // manually assign the User instance to Kiki's Core and store the
    // authentication cookie:

    // \Kiki\Core::setUser($user);
    // \Kiki\Auth::setCookie($user->id(), $cookieConsent );

    // Upon failure, implementation should reset the User class:

    // $user->reset();

    $mfaUserId = $_SESSION['mfaUserId'] ?? null;

    if ( $userId )
    {
      $user->load($userId);

      $uMeta = $user->getMetaData();
      $mfaEnabled = $uMeta->getValue( 'mfa_enabled' );

      if ( !$user->isVerified() )
      {
        $this->errors[] = array( 'msg' => "Your e-mail address has not been verified yet." );
      }
      else if ( $mfaEnabled )
      {
        $this->data['mfaForm'] = true;

        if ( !isset($_SESSION) )
          @session_start();

        $_SESSION['mfaUserId'] = $user->id();
      }
      else
      {
        \Kiki\Core::setUser($user);
        \Kiki\Auth::setCookie($user->id(), $cookieConsent );

        // Redirect to home page (local namespaced class can override this behaviour if necessary)
        $this->status = 303;
        $this->content = '/';

        return true;
      }
    }
    else if ( $mfaUserId )
    {
    }
    else if ( $_POST )
    {
      $this->errors[] = array( 'msg' => "Invalid email/password combination." );
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
    $this->data['email'] = $email;

    $validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
    if ( !$validEmail )
      $this->errors['email'] = array( 'msg' => _("You did not enter a valid e-mail address.") );

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

    $template->assign( 'serverName', $_SERVER['SERVER_NAME'] );
    $template->assign( 'url', $url );

    $html = $template->content();

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
    {
      $this->errors['unavailable'] = array( 'msg' => 'Invalid or expired token.' );
    }

    $user->load($userId);
    $this->data['email'] = $user->email();

    $password = $_POST['password'] ?? null;
    if ( $_POST && !$password )
      $this->errors['password'] = array( 'msg' => _("Your password cannot be empty.") );

    $this->data['password'] = $password;

    if ( $_POST && !count($this->errors) )
    {
      Log::debug( sprintf( 'Updating password hash for user [%d][%s], identified by token [%s]',
        $user->id(),
        $user->objectName(),
        $token,
      ) );

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

    $user = Core::getUser();

    if ( $user->id() )
    {
      $user->reset();
      Auth::setCookie(0);

      $this->warnings[] = array( 'msg' => "You have been logged out from the account you were signed in to." );
      \Kiki\Core::getFlashBag()->add( 'warning', _("You have been logged out from the account you were signed in to.") );
    }

    if ( $_POST )
    {
      $email = $_POST['email'] ?? null;
      $password = $_POST['password'] ?? null; 

      $validEmail = preg_match( '/^[A-Z0-9+._%-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i', trim($email) );
      if ( !$validEmail )
        $this->errors['email'] = array( 'msg' => _("You did not enter a valid e-mail address.") );

      if ( !count($this->errors) )
      {
        $userId = $user->getIdByEmail( $email );
        // Disable to send email despite existing account
        if ( $userId )
          $this->errors[] = array( 'msg' => 'An account with that e-mail address already exists. <a href="/request-password-reset" class="bk">Request password reset</a>.' );
      }

      if ( !$password )
        $this->errors['password'] = array( 'msg' => _("Your password cannot be empty.") );

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
        $user->storeNew( $email, $password, $createAdmin );

        if ( !$user->id() )
        {
          $this->errors[] = array( 'msg' => "Account creation failed." );
        }
        else
        {
          $authToken = $user->getAuthToken();

          $this->data['createdUserId'] = $user->id();
          $this->data['createdUserObjectId'] = $user->objectId();

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

            $template->assign( 'serverName', $_SERVER['SERVER_NAME'] );
            $template->assign( 'url', $url );

            $html = $template->content();

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

    return true;
  }

  public function verifyAction()
  {
    $this->status = 200;
    $this->template = 'pages/login';
    $this->title = _("Verify account");

    $user = \Kiki\Core::getUser();
    if ( $user->isVerified() )
    {
      $this->template = null;
      $this->status = 303;
      $this->content = '/login';
      return true;
    }

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
        $this->data['verifiedUserId'] = $verifyUserId;

        if ( $user->id() && $user->id() != $verifyUser->id() )
        {
          Auth::setCookie(0);

          $this->warnings[] = array( 'msg' => sprintf( "Because you verified account <strong>%s</strong> (%d), you are no longer logged in as <strong>%s</strong> (%d).", $verifyUser->email(), $verifyUser->id(), $user->email(), $user->id() ) );

          $user->reset();
        }
        else
        {
          // Disabled, users should still login after e-mail verification.
          // Auth::setCookie( $verifyUser->id() );
          // $user = $verifyUser;
          // Core::setUser($verifyUser);
          $this->notices[] = array( 'msg' => "Your e-mail address was succesfully verified. You can now log in." );
          Core::getFlashBag()->add( 'verifiedUser', $verifyUser->email() );

          $mainTemplate = \Kiki\Template::getInstance();
          $mainTemplate->assign('user', $user->templateData() );
        }
      }
    }

    return true;
  }
}
