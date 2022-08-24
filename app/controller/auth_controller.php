<?php
switch ( $fusebox->action ) :


	case 'index':
		// when already logged in
		// ===> go to landing page (or callback)
		if ( !empty($arguments['callback']) ) F::redirect(base64_decode($arguments['callback']), Auth::user());
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit points
		$xfa['sso'] = 'sso'.( !empty($arguments['callback']) ? "&callback={$arguments['callback']}" : '' );
		$xfa['init'] = "{$fusebox->controller}.init".( !empty($arguments['callback']) ? "&callback={$arguments['callback']}" : '' );
		$xfa['local'] = "{$fusebox->controller}.form".( !empty($arguments['callback']) ? "&callback={$arguments['callback']}" : '' );
		// create default account (when necessary)
		$userCount = ORM::count('user');
		F::error(ORM::error(), $userCount === false);
		F::redirect($xfa['init'], !$userCount);
		// go straight to login form (when sso not available)
		F::redirect($xfa['local'], !file_exists(F::appPath('controller/sso_controller.php')));
		// display buttons (to choose between sso-login or local-login)
		ob_start();
		include F::appPath('view/auth/index.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) echo $layout['content'];
		else include F::appPath('view/auth/layout.php');
		break;


	case 'form':
		// when already logged in
		// ===> go to landing page (or callback)
		if ( !empty($arguments['callback']) ) F::redirect(base64_decode($arguments['callback']), Auth::user());
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.login".( !empty($arguments['callback']) ? "&callback={$arguments['callback']}" : '' );
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
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.reset";
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


	case 'reset':
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
		// exit points
		$xfa['success'] = !empty($arguments['callback']) ? base64_decode($arguments['callback']) : F::config('defaultCommand');
		$xfa['failure'] = "{$fusebox->controller}.form".( !empty($arguments['callback']) ? "&callback={$arguments['callback']}" : '' );
		// when failure
		// ===> show message and form
		if ( $loginResult === false ) $_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
		F::redirect($xfa['failure'], $loginResult === false);
		// when success
		// ===> go to landing page (or callback)
		F::redirect($xfa['success']);
		break;


	case 'logout':
		// perform sso logout (when available)
		F::redirect('sso.logout', file_exists(F::appPath('controller/sso_controller.php')));
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
		$xfa['redirect'] = file_exists(F::appPath('controller/sso_controller.php')) ? $fusebox->controller : "{$fusebox->controller}.form";
		if ( !empty($arguments['callback']) ) $xfa['redirect'] .= "&callback={$arguments['callback']}";
		F::redirect($xfa['redirect']);
		break;


	default:
		F::pageNotFound();


endswitch;