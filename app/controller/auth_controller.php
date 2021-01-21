<?php
switch ( $fusebox->action ) :


	case 'index':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// go to login form when no CAS login
		F::redirect('auth.form', !file_exists(F::appPath('controller/cas_controller.php')));
		// exit point
		$xfa['cas'] = 'cas';
		$xfa['local'] = 'auth.form';
		// display
		ob_start();
		include F::appPath('view/auth/index.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::appPath('view/auth/layout.php');
		}
		break;


	case 'form':
		// go to default page when logged in
		F::redirect(F::config('defaultCommand'), Auth::user());
		// create default account (when necessary)
		F::redirect('auth.init', ORM::count('user') == 0);
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
		include F::appPath('view/auth/login.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::appPath('view/auth/layout.php');
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
		include F::appPath('view/auth/forgot.php');
		$layout['content'] = ob_get_clean();
		// layout (when necessary)
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			include F::appPath('view/auth/layout.php');
		}
		break;


	case 'reset-password':
		F::error('No email was provided', empty($arguments['data']['email']));
		$resetResult = Auth::resetPassword($arguments['data']['email']);
		$_SESSION['flash'] = array(
			'type'    => ( $resetResult === false ) ? 'danger' : 'success',
			'message' => ( $resetResult === false ) ? Auth::error() : "New password has been sent to <strong><em>{$arguments['data']['email']}<em></strong>",
		);
		F::redirect('auth.forgot');
		break;


	case 'login':
		F::error('No data were submitted', empty($arguments['data']));
		// proceed to login
		$authResult = Auth::login($arguments['data']);
		// write log
		if ( isset($arguments['data']['email']) ) {
			$uid = $arguments['data']['email'];
		} elseif ( isset($arguments['data']['username']) ) {
			$uid = $arguments['data']['username'];
		} else {
			$uid = '';
		}
		if ( method_exists('Log', 'write') ) {
			if ( $authResult === false ) {
				$remark  = 'FAILED'.PHP_EOL;
				$remark .= '[username] '.$uid.PHP_EOL;
				$remark .= '[ip] '.$_SERVER['REMOTE_ADDR'].PHP_EOL;
				$remark .= '[error] '.Auth::error();
			}
			$logResult = Log::write(array(
				'action' => 'LOGIN',
				'remark' => !empty($remark) ? $remark : null,
			));
			F::error(Log::error(), $logResult === false);
		}
		// failure : show message
		if ( $authResult === false ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => Auth::error());
			F::redirect('auth.form');
		}
		// success : go to default page
		F::redirect(F::config('defaultCommand'));
		break;


	case 'logout':
		$logoutResult = Auth::logout();
		F::error(Auth::error(), $logoutResult === false);
		F::redirect('auth');
		break;


	case 'init':
		$initResult = Auth::initUser($defaultUser);
		$_SESSION['flash'] = array(
			'type'    => ( $initResult === false ) ? 'danger' : 'success',
			'message' => ( $initResult === false ) ? Auth::error() : "{$defaultUser['role']} account created ({$defaultUser['username']}:{$defaultUser['password']})",
		);
		F::redirect('auth.form');
		break;


	default:
		F::pageNotFound();


endswitch;