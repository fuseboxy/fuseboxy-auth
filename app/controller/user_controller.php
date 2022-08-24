<?php
F::redirect('auth&callback='.base64_encode($_SERVER['REQUEST_URI']), !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// default role
$arguments['role'] = $arguments['role'] ?? Auth::user('role') ?: '';
// disallow user to see role with higher privilege
if ( $arguments['role'] == 'SUPER' and Auth::user('role') != 'SUPER' ) $arguments['role'] = Auth::user('role');


// run!
switch ( $fusebox->action ) :


	// data manipulation before save
	case 'save':
		if ( isset($arguments['data']['password'])     ) $arguments['data']['password']     = trim($arguments['data']['password']);
		if ( isset($arguments['data']['new_password']) ) $arguments['data']['new_password'] = trim($arguments['data']['new_password']);
		// do not allow empty password (when create user)
		if ( isset($arguments['data']['password']) and $arguments['data']['password'] === '' ) {
			$arguments['data']['password'] = Auth::generateRandomPassword(64);
			F::error(Auth::error(), $arguments['data']['password'] === false);
		}
		// change password (when specified)
		if ( isset($arguments['data']['new_password']) and strlen($arguments['data']['new_password']) ) {
			$arguments['data']['password'] = $arguments['data']['new_password'];
		}
		// remove new password (because no such field in database)
		if ( isset($arguments['data']['new_password']) ) unset($arguments['data']['new_password']);
		// perform password hashing (when password specified)
		if ( isset($arguments['data']['password']) ) {
			$arguments['data']['password'] = Auth::hashPassword($arguments['data']['password']);
			F::error(Auth::error(), $arguments['data']['password'] === false);
		}
		// no break, and continue scaffold process...


	// crud operations
	default:
		// only show [new-password] field in edit form when password hash
		$enterNewPassword = ( F::is('*.edit') and Auth::$hashPassword );
		// config
		$scaffold = array_merge([
			'beanType' => 'user',
			'retainParam' => array('role' => $arguments['role']),
			'editMode' => 'inline',
			'allowDelete' => Auth::userInRole('SUPER'),
			'layoutPath' => F::appPath('view/user/layout.php'),
			'listFilter' => array('role = ?', array($arguments['role'])),
			'listOrder' => 'ORDER BY username',
			'listField' => array(
				'id' => '60',
				'role|fullname' => '20%',
				( $enterNewPassword ? 'username|new_password' : 'username|password' ) => '25%',
				'email|tel' => '25%',
			),
			'fieldConfig' => array(
				'id',
				'role' => array('icon' => 'fa fa-tag small', 'default' => $arguments['role'], 'required' => true, 'readonly' => !Auth::userInRole('SUPER')),
				'username' => array('icon' => 'fa fa-user small', 'placeholder' => true, 'required' => true),
				'password' => array('icon' => 'fa fa-key small', 'placeholder' => true),
				'new_password' => $enterNewPassword ? array('icon' => 'fa fa-key small', 'placeholder' => true) : false,
				'fullname' => array('label' => 'Full Name', 'placeholder' => 'Full Name'),
				'email' => array('icon' => 'fa fa-envelope small', 'placeholder' => true),
				'tel' => array('icon' => 'fa fa-phone small', 'placeholder' => true)
			),
			'scriptPath' => array(
				'row' => F::appPath('view/user/row.php'),
			),
			'writeLog' => class_exists('Log'),
		], $userScaffold ?? $user_scaffold ?? []);
		// component
		include F::appPath('controller/scaffold_controller.php');


endswitch;