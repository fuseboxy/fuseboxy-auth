<?php
F::redirect('auth', !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// default role & retain selected role
if ( !isset($_SESSION['userController__userRole']) ) $_SESSION['userController__userRole'] = Auth::user('role');
if ( isset($arguments['role']) ) $_SESSION['userController__userRole'] = $arguments['role'];
// disallow user to see role with higher privilege
if ( $_SESSION['userController__userRole'] == 'SUPER' and Auth::user('role') != 'SUPER' ) {
	$_SESSION['userController__userRole'] = Auth::user('role');
}


// run!
switch ( $fusebox->action ) {


	// data manipulation before save
	case 'save':
		// do not allow empty password (when create user)
		if ( isset($arguments['data']['password']) and trim($arguments['data']['password']) === '' ) {
			$arguments['data']['password'] = Auth::generateRandomPassword(64);
			F::error(Auth::error(), $arguments['data']['password'] === false);
		}
		// change password (when specified)
		if ( !empty($arguments['data']['new_password']) ) $arguments['data']['password'] = $arguments['new_password'];
		// remove new password (because no such field in database)
		if ( isset($arguments['data']['new_password']) ) unset($arguments['data']['new_password']);
		// perform password hashing (when password specified)
		$arguments['data']['password'] = Auth::hashPassword($arguments['data']['password']);
		F::error(Auth::error(), $arguments['data']['password'] === false);
		// [NOTE] no break ===> save by scaffold


	// crud operations
	default:
		// config
		$scaffold = array(
			'beanType' => 'user',
			'editMode' => 'inline',
			'allowDelete' => Auth::userInRole('SUPER'),
			'layoutPath' => F::appPath('view/user/layout.php'),
			'listFilter' => array('role = ?', array($_SESSION['userController__userRole'])),
			'listOrder' => 'ORDER BY username',
			'listField' => array(
				'id' => '60',
				'role|fullname' => '20%',
				'username|password' => '25%',
				'email|tel' => '25%',
			),
			'fieldConfig' => array(
				'id',
				'role' => array('icon' => 'fa fa-tag small', 'default' => $_SESSION['userController__userRole'], 'readonly' => !Auth::userInRole('SUPER')),
				'username' => array('icon' => 'fa fa-user small', 'placeholder' => true),
F::is('*.edit') ? 'new_password' : 'password' => call_user_func(function(){
	// no hash : simply show and edit password as normal field
	if ( !Auth::$hashPassword ) {
		return array('icon' => 'fa fa-key small', 'placeholder' => true);
	// password hash : show message at listing
	} elseif ( F::is('*.index,*.row') ) {
		return array('format' => 'output', 'value' => '<span class="text-muted">[PASSWORD HASHED]</span>');
	// password hash : show as empty field when edit
	} else {
		return array('icon' => 'fa fa-key small', 'placeholder' => F::is('*.edit') ? 'New Password' : true, 'value' => '');
	}
}),
				'fullname' => array('label' => 'Full Name', 'placeholder' => 'Full Name'),
				'email' => array('icon' => 'fa fa-envelope small', 'placeholder' => true),
				'tel' => array('icon' => 'fa fa-phone small', 'placeholder' => true)
			),
			'writeLog' => class_exists('Log'),
		);
		// component
		include F::appPath('controller/scaffold_controller.php');


endswitch;