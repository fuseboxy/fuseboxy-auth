<?php
switch ( $fusebox->action ) :


	case 'index':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// go to login form when no CAS login
		F::redirect('auth.form', !file_exists(__DIR__.'/cas_controller.php'));
		// exit point
		$xfa['cas'] = 'cas';
		$xfa['local'] = 'auth.form';
		// display
		ob_start();
		include F::config('appPath').'view/auth/index.php';
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::config('appPath').'view/auth/layout.php';
		}
		break;


	case 'form':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// create default account (when necessary)
		F::redirect('auth.init', R::count('user') == 0);
		// exit point
		$xfa['submit'] = 'auth.login';
		if ( class_exists('Util') ) {
			$xfa['forgot'] = 'auth.forgot';
		}
		// display : captcha
		if ( class_exists('Captcha') ) {
			$layout['captcha'] = Captcha::getField();
			F::error(Captcha::error(), $layout['captcha'] === false);
		}
		// display
		ob_start();
		include F::config('appPath').'view/auth/login.php';
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::config('appPath').'view/auth/layout.php';
		}
		break;


	case 'forgot':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit point
		$xfa['submit'] = 'auth.reset-password';
		$xfa['login'] = 'auth.form';
		// display : captcha
		if ( class_exists('Captcha') ) {
			$layout['captcha'] = Captcha::getField();
			F::error(Captcha::error(), $layout['captcha'] === false);
		}
		// display
		ob_start();
		include F::config('appPath').'view/auth/forgot.php';
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::config('appPath').'view/auth/layout.php';
		}
		break;


	case 'reset-password':
		F::error('No email was provided', empty($arguments['data']['email']));
		// proceed to reset password
		$resetResult = Auth::resetPassword($arguments['data']['email']);
		// show message
		$_SESSION['flash'] = array(
			'type'    => ( $resetResult === false ) ? 'danger' : 'success',
			'message' => ( $resetResult === false ) ? Auth::error() : "New password has been sent to <strong><em>{$arguments['data']['email']}<em></strong>",
		);
		// write log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'reset-password',
				'remark' => $mailResult ? '' : Util::error(),
			));
			F::error(Log::error(), $logResult === false);
		}
		// show message
		F::redirect('auth.forgot');
		break;


	case 'login':
		F::error('No data were submitted', empty($arguments['data']));
		// proceed to login
		$loginResult = Auth::login($arguments['data']);
		// write log
		if ( isset($arguments['data']['email']) ) {
			$uid = $arguments['data']['email'];
		} elseif ( isset($arguments['data']['username']) ) {
			$uid = $arguments['data']['username'];
		} else {
			$uid = '';
		}
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'LOGIN',
				'remark' => ( $loginResult === false ) ? "FAILED\n[username] {$uid}\n[ip] {$_SERVER['REMOTE_ADDR']}" : null,
			));
			F::error(Log::error(), $logResult === false);
		}
		// failure : show message
		if ( $loginResult === false ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
			F::redirect('auth.form');
		}
		// success : go to default page
		F::redirect(F::config('defaultCommand'));
		break;


	case 'logout':
		$username = Auth::user('username');
		$logoutResult = Auth::logout();
		F::error(Auth::error(), $logoutResult === false);
		// write log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'LOGOUT',
				'username' => $username
			));
			F::error(Log::error(), $logResult === false);
		}
		// return to index page
		F::redirect('auth');
		break;


	case 'init':
		// create first user (when necessary)
		$initResult = Auth::initUser($defaultUser);
		F::error(Auth::error(), $initResult === false);
		// show message
		$_SESSION['flash'] = array(
			'type'    => ( $initResult === false ) ? 'danger' : 'success',
			'message' => ( $initResult === false ) ? Auth::error() : "{$defaultUser['role']} account created ({$defaultUser['username']}:{$defaultUser['password']})",
		);
		// write log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'username' => 'SYSTEM',
				'action' => 'INIT_USER',
				'remark' => $_SESSION['flash']['message'],
			));
			F::error(Log::error(), $logResult === false);
		}
		// return to form
		F::redirect('auth.form');
		break;


	default:
		F::pageNotFound();


endswitch;