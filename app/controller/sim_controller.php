<?php
F::redirect('auth', !Auth::user());
F::error('Disallowed', !Auth::userInRole('SUPER,ADMIN'));


// run!
switch ( $fusebox->action ) :


	case 'index':
	case 'dropdown':
		// get all users (non-super)
		$filter = "id != ? AND role != 'SUPER' AND IFNULL(disabled, 0) = 0 ORDER BY role, username";
		$param =  array( Auth::user('id') );
		$data = R::find('user', $filter, $param);
		// group users by role
		$users = array();
		foreach ( $data as $id => $item ) {
			if ( !isset($users[$item->role]) ) $users[$item->role] = array();
			$users[$item->role][$id] = $item;
		}
		// exit point
		if ( !empty($users) ) $xfa['start'] = "{$fusebox->controller}.start";
		if ( Sim::user() ) $xfa['end'] = "{$fusebox->controller}.end";
		// display
		ob_start();
		if ( F::is('*.dropdown') ) {
			include F::config('appPath').'view/sim/dropdown.php';
		} else {
			include F::config('appPath').'view/sim/index.php';
		}
		$layout['content'] = ob_get_clean();
		// layout
		if ( F::ajaxRequest() ) {
			echo $layout['content'];
		} else {
			$arguments['breadcrumb'] = array('User Simulation');
			include F::config('appPath').'view/global/layout.php';
		}
		break;


	case 'start':
		F::error('No user was specified', empty($arguments['user_id']));
		// start (or show error when neccessary)
		$simResult = Sim::start($arguments['user_id']);
		F::error(Sim::error(), $simResult === false);
		// write log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write('START_USER_SIM');
			F::error(Log::error(), $logResult === false);
		}
		// go to default page, or...
		F::redirect(F::config('defaultCommand'), empty($arguments['callback']));
		// go to (base64-encoded) callback if defined
		F::redirect(base64_decode($arguments['callback']));
		break;


	case 'end':
		$simUser = Sim::user('username');
		// end (or show error when necessary)
		$simResult = Sim::end();
		F::error(Sim::error(), $simResult === false);
		// write log
		if ( method_exists('Log', 'write') ) {
			$logResult = Log::write( array( 'action' => 'END_USER_SIM', 'sim_user' => $simUser ) );
			F::error(Log::error(), $logResult === false);
		}
		// go to default page, or...
		F::redirect(F::config('defaultCommand'), empty($arguments['callback']));
		// go to (base64-encoded) callback if defined
		F::redirect(base64_decode($arguments['callback']));
		break;


	default:
		F::pageNotFound();


endswitch;