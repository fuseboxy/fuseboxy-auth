<?php
F::redirect('auth', !Auth::user());
F::redirect(F::config('defaultCommand'), !Auth::activeUserInRole('SUPER,ADMIN'));


// default role
if ( !isset($_SESSION['userController__userRole']) ) {
	$_SESSION['userController__userRole'] = Auth::activeUser('role');
}


// change selected role (pass by url from layout)
if ( isset($arguments['role']) ) {
	$_SESSION['userController__userRole'] = $arguments['role'];
}


// disallow user to see role with higher privilege
if ( $_SESSION['userController__userRole'] == 'SUPER' and Auth::activeUser('role') != 'SUPER' ) {
	$_SESSION['userController__userRole'] = Auth::activeUser('role');
}


// avoid update with empty password
if ( isset($arguments['data']['password']) and empty($arguments['data']['password']) ) {
	unset($arguments['data']['password']);
}
// perform password hashing before save (when neccessary)
if ( F::is('*.save') and !empty($arguments['data']['password']) ) {
	$arguments['data']['password'] = Auth::hashPassword($arguments['data']['password']);
} 


// config
$scaffold = array(
	'beanType' => 'user',
	'editMode' => 'inline',
	'allowDelete' => Auth::activeUserInRole('SUPER'),
	'layoutPath' => F::config('appPath').'view/user/layout.php',
	'listFilter' => array('role = ?', array($_SESSION['userController__userRole'])),
	'listOrder' => 'ORDER BY username',
	'listField' => array(
		'id' => '5%',
		'role|fullname' => '20%',
		'username|password' => '25%',
		'email|tel' => '25%',
	),
	'fieldConfig' => array(
		'--' => array('format' => 'output', 'value' => '<hr class="my-2 mx-0" />'),
		'id' => array(),
		'role' => array('default' => $_SESSION['userController__userRole'], 'readonly' => !Auth::activeUserInRole('SUPER')),
		'username' => array('placeholder' => true),
		'password' => call_user_func(function(){
			// no hash : simply show and edit password as normal field
			if ( !Auth::$hashPassword ) {
				return array('placeholder' => true);
			// password hash : show message at listing
			} elseif ( F::is('*.index,*.row') ) {
				return array('format' => 'output', 'value' => '<span class="text-muted">[PASSWORD HASHED]</span>');
			// password hash : show as empty field when edit
			} else {
				return array('placeholder' => F::is('*.edit') ? 'New Password' : true, 'value' => '');
			}
		}),
		'fullname' => array('label' => 'Full Name', 'placeholder' => 'Full Name'),
		'email' => array('placeholder' => true),
		'tel' => array('placeholder' => true)
	),
	'writeLog' => true,
);


// run!
include F::config('appPath').'controller/scaffold_controller.php';