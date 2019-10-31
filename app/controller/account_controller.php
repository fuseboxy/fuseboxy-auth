<?php
F::redirect('auth', !Auth::user());


// run!
switch ( $fusebox->action ) :


	case 'profile':
		// get record
		$user = R::load('user', Auth::user('id'));
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.update_profile";
		// display
		ob_start();
		include F::config('appPath').'view/account/profile.php';
		$layout['content'] = ob_get_clean();
		// breadcrumb
		$arguments['breadcrumb'] = array('My Account', 'Update Profile');
		// layout
		include F::config('appPath').'view/account/layout.php';
		break;


	case 'password':
		// exit point
		$xfa['submit'] = "{$fusebox->controller}.update_password";
		// display
		ob_start();
		include F::config('appPath').'view/account/password.php';
		$layout['content'] = ob_get_clean();
		// breadcrumb
		$arguments['breadcrumb'] = array('My Account', 'Change Password');
		// layout
		include F::config('appPath').'view/account/layout.php';
		break;


	case 'update_profile':
		F::error('No data were submitted', empty($arguments['data']));
		// update record
		// ===> remember {beforeSave|afterSave} for log
		$bean = R::load('user', Auth::user('id'));
		$beforeSave = $bean->export();
		$bean->import($arguments['data']);
		$afterSave = $bean->export();
		$saveResult = R::store($bean);
		F::error('Error occurred while updating profile', empty($saveResult));
		// refresh session
		$refreshResult = Auth::refresh();
		F::error(Auth::error(), $refreshResult === false);
		// write log (when necessary)
		if ( class_exists('Log') ) {
			$logResult = Log::write(array(
				'action' => 'UPDATE_ACCOUNT_PROFILE',
				'remark' => class_exists('Bean', 'diff') ? Bean::diff($beforeSave, $afterSave) : null,
			));
			F::error(Log::error(), !$logResult);
		}
		// done!
		$_SESSION['flash'] = array('type' => 'success', 'message' => 'Profile updated successfully');
		F::redirect("{$fusebox->controller}.profile");
		break;


	case 'update_password':
		// validation
		if ( empty($arguments['new_password']) ) {
			$err = 'New password was required';
		} elseif ( empty($arguments['confirm_password']) ) {
			$err = 'Confirm password was required';
		} elseif ( $arguments['new_password'] != $arguments['confirm_password'] ) {
			$err = 'New password and confirm passowrd do not match';
		}
		if ( !empty($err) ) {
			$_SESSION['flash'] = array('type' => 'danger', 'message' => $err);
			F::redirect("{$fusebox->controller}.password");
		}
		// update record
		$bean = R::load('user', Auth::user('id'));
		$bean->password = Auth::$hashPassword ? password_hash($arguments['new_password'], PASSWORD_DEFAULT) : $arguments['new_password'];
		$saveResult = R::store($bean);
		F::error('Error occurred while changing password', empty($saveResult));
		// refresh session
		$refreshResult = Auth::refresh();
		F::error(Auth::error(), $refreshResult === false);
		// write log (when necessary)
		if ( class_exists('Log') ) {
			$logResult = Log::write('UPDATE_ACCOUNT_PASSWORD');
			F::error(Log::error(), !$logResult);
		}
		// done!
		$_SESSION['flash'] = array('type' => 'success', 'message' => 'Password changed successfully');
		F::redirect("{$fusebox->controller}.password");
		break;


	default:
		F::pageNotFound();


endswitch;