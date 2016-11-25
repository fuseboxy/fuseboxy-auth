<?php
// create super account (when necessary)
F::redirect('auth.init', !F::is('auth.init') and ( R::count('user') == 0 ) );


// run...
switch ( $fusebox->action ) :


	// login form
	case 'index':
		// when user already logged in...
		// ===> go to default page if no callback defined
		// ===> go to (base64-encoded) callback if defined
		if ( Auth::user() ) {
			F::redirect(F::config('defaultCommand'), empty($arguments['callback']));
			F::redirect(base64_decode($arguments['callback']));
		}
		// exit point
		$xfa['submit'] = 'auth.login';
		if ( !empty($arguments['callback']) ) {
			$xfa['submit'] .= "&callback={$arguments['callback']}";
		}
		$xfa['forgot'] = 'auth.forgot';
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


	// forgot password
	case 'forgot':
		// exit point
		$xfa['submit'] = 'auth.reset-password';
		$xfa['login'] = 'auth.index';
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
		// check email
		$arguments['data']['email'] = trim($arguments['data']['email']);
		$user = R::findOne('user', 'email = ? ', array($arguments['data']['email']));
		if ( empty($user->id) ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => "Email {{$arguments['data']['email']}} not found");
			F::redirect('auth.forgot');
		}
		// random password
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$password = substr(str_shuffle($chars), 0, 8);
		// save new (random) password
		$user->password = $password;
		R::store($user);
		// prepare mail
		$mail = array(
			'from_name' => 'Please do not reply',
			'from' => 'noreply@metaseit.com',
			'to' => $arguments['data']['email'],
			'subject' => 'Your password was reset',
			'body' => "New password : <strong>{$password}</strong>"
		);
		// send mail (do not send when unit test)
		if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) {
			$mailResult = Util::sendMail($mail);
		} else {
			$mailResult = true;
		}
		if ( !$mailResult ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => Util::error());
		} else {
			$_SESSION['flash'] = array('type' => 'success', 'message' => 'New password has been sent to your mailbox');
		}
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
		// login (by username, then by email)
		$result = Auth::login($arguments['data']);
		// try email if not succeed...
		if ( !$result ) {
			$arguments['data']['email'] = $arguments['data']['username'];
			unset($arguments['data']['username']);
			$result = Auth::login($arguments['data']);
		}
		// show message when login failure
		if ( !$result ) {
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
		// return to login form, or...
		// ===> go to (base64-encoded) callback if defined
		F::redirect('auth', empty($arguments['callback']));
		F::redirect(base64_decode($arguments['callback']));
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
		// ===> go to (base64-encoded) callback if defined
		F::redirect('auth', empty($arguments['callback']));
		F::redirect(base64_decode($arguments['callback']));
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
		F::error('User account already exists', R::count('user') != 0);
		// create default user
		$bean = R::dispense('user');
		$bean->import(array(
			'username' => 'developer',
			'password' => '12345678',
			'role' => 'SUPER',
		));
		$result = R::store($bean);
		// show message
		$_SESSION['flash'] = array(
			'type' => 'success',
			'message' => "Super account was created ({$bean->username} : {$bean->password})"
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
