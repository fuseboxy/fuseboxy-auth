<?php
// check any custom SSO module
$hasSSO = file_exists( F::appPath('controller/sso_controller.php') );


// run!
switch ( $fusebox->action ) :


	case 'index':
		// go to default page (when already signed in)
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit point
		$xfa['sso'] = 'sso';
		$xfa['local'] = "{$fusebox->controller}.form";
		// go to login form (when sso not available)
		F::redirect($xfa['local'], !$hasSSO);
		// display
		ob_start();
		include F::appPath('view/auth/index.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) echo $layout['content'];
		else include F::appPath('view/auth/layout.php');
		break;


	case 'form':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// create default account (when necessary)
		$userCount = ORM::count('user');
		F::error(ORM::error(), $userCount === false);
		F::redirect("{$fusebox->controller}.init", $userCount == 0);
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.login";
		if ( !empty(F::config('smtp')) ) $xfa['forgot'] = "{$fusebox->controller}.forgot";
		// display : captcha
		if ( !empty(F::config('captcha')) ) {
			F::error('Class [Captcha] is reqiured', !class_exists('Captcha'));
			$layout['captcha'] = Captcha::getField();
			F::error(Captcha::error(), $layout['captcha'] === false);
		}
		// display
		ob_start();
		include F::appPath('view/auth/login.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) echo $layout['content'];
		else include F::appPath('view/auth/layout.php');
		break;


	case 'forgot':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.reset-password";
		$xfa['login'] = "{$fusebox->controller}.form";
		// display : captcha
		if ( !empty(F::config('captcha')) ) {
			F::error('Class [Captcha] is reqiured', !class_exists('Captcha'));
			$layout['captcha'] = Captcha::getField();
			F::error(Captcha::error(), $layout['captcha'] === false);
		}
		// display
		ob_start();
		include F::appPath('view/auth/forgot.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) echo $layout['content'];
		else include F::appPath('view/auth/layout.php');
		break;


	case 'reset-password':
		F::error('No email was provided', empty($arguments['data']['email']));
		// proceed to reset
		$resetResult = Auth::resetPassword($arguments['data']['email']);
		// prepare message
		if ( $resetResult === false ) $_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
		else $_SESSION['flash'] = array('type' => 'success', 'message' => "New password has been sent to <strong><em>{$arguments['data']['email']}<em></strong>");
		// back to form (with message)
		F::redirect("{$fusebox->controller}.forgot");
		break;


	case 'login':
		F::error('No data were submitted', empty($arguments['data']));
		// proceed to login
		$loginResult = Auth::login($arguments['data']);
		// failure : show message
		if ( $loginResult === false ) $_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
		F::redirect("{$fusebox->controller}.form", isset($_SESSION['flash']['type']) and $_SESSION['flash']['type'] == 'danger');
		// success : go to default page
		F::redirect(F::config('defaultCommand'));
		break;


	case 'logout':
		// perform sso logout (when available)
		F::redirect('sso.logout', $hasSSO);
		// proceed to logout
		$logoutResult = Auth::logout();
		F::error(Auth::error(), $logoutResult === false);
		// return to login form
		F::redirect($fusebox->controller);
		break;


	case 'init':
		$initResult = Auth::initUser($defaultUser);
		// prepare message
		if ( $initResult === false ) $_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
		else $_SESSION['flash'] = array('type' => 'success', 'message' => "{$defaultUser['role']} account created ({$defaultUser['username']}:{$defaultUser['password']})");
		// back to form (with message)
		F::redirect("{$fusebox->controller}.form");
		break;


	default:
		F::pageNotFound();


endswitch;