<?php
switch ( $fusebox->action ) :


	case 'index':
		// go to default page when user already logged in
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
		// go to default page when user already logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// exit point
		$xfa['submit'] = 'auth.reset-password';
		$xfa['login'] = 'auth.index';
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
		// save log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'reset-password',
				'remark' => $mailResult ? '' : Util::error(),
			));
			F::error(Log::error(), !$logResult);
		}
		// show message
		F::redirect('auth.forgot');
		break;


	case 'login':
		F::error('No data were submitted', empty($arguments['data']));
		// proceed to login
		$loginResult = Auth::login($arguments['data']);
		// show message when login failure
		if ( $loginResult === false ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
		}
		// save log
		if ( isset($arguments['data']['email']) ) {
			$uid = $arguments['data']['email'];
		} elseif ( isset($arguments['data']['username']) ) {
			$uid = $arguments['data']['username'];
		} else {
			$uid = '';
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'LOGIN',
				'remark' => $result ? '' : "FAILED\n[username] {$uid}\n[ip] {$ip}",
			));
			F::error(Log::error(), !$logResult);
		}
		// return to login form
		F::redirect('auth');
		break;


	case 'logout':
		$username = Auth::user('username');
		$result = Auth::logout();
		F::error(Auth::error(), !$result);
		// save log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'LOGOUT',
				'username' => $username
			));
			F::error(Log::error(), !$logResult);
		}
		// return to login form, or...
		F::redirect('auth');
		break;


	case 'start-sim':
		F::error('Disallowed', !Auth::userInRole('SUPER,ADMIN'));
		F::error('No user was specified', empty($arguments['user_id']));
		// start (or show error when neccessary)
		$result = Sim::start($arguments['user_id']);
		F::error(Sim::error(), !$result);
		// save log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write('START_USER_SIM');
			F::error(Log::error(), !$logResult);
		}
		// go to default page, or...
		// ===> go to (base64-encoded) callback if defined
		F::redirect(F::config('defaultCommand'), empty($arguments['callback']));
		F::redirect(base64_decode($arguments['callback']));
		break;


	case 'end-sim':
		F::error('Disallowed', !Auth::userInRole('SUPER,ADMIN'));
		// end (or show error when necessary)
		$sim_user = Sim::user('username');
		$result = Sim::end();
		F::error(Sim::error(), !$result);
		// save log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'action' => 'END_USER_SIM',
				'sim_user' => $sim_user
			));
			F::error(Log::error(), !$logResult);
		}
		// go to default page, or...
		// ===> go to (base64-encoded) callback if defined
		F::redirect(F::config('defaultCommand'), empty($arguments['callback']));
		F::redirect(base64_decode($arguments['callback']));
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
		// save log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write(array(
				'username' => 'SYSTEM',
				'action' => 'INIT_USER',
				'remark' => $_SESSION['flash']['message'],
			));
			F::error(Log::error(), !$logResult);
		}
		// return to form
		F::redirect('auth');
		break;


	default:
		F::pageNotFound();


endswitch;