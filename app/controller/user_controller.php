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


// hash password when save (when neccessary)
if ( Auth::$hashPassword and F::is('*.save') and isset($arguments['data']['password']) ) {
	$arguments['data']['password'] = password_hash($arguments['data']['password'], PASSWORD_DEFAULT);
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
		'username|password' => '20%',
		'email|tel' => ''
	),
	'fieldConfig' => array(
		'id' => array(),
		'username' => array('placeholder' => 'Username'),
		'password' => Auth::$hashPassword ? array('format' => 'output', 'value' => '(password hashed)') : array('placeholder' => 'Password'),
		'role' => array('default' => $_SESSION['userController__userRole'], 'readonly' => !Auth::activeUserInRole('SUPER')),
		'fullname' => array('label' => 'Full Name', 'placeholder' => 'Full Name'),
		'email' => array('placeholder' => 'Email'),
		'tel' => array('placeholder' => 'Tel')
	),
	'writeLog' => true,
);


// run!
$layout['width'] = 'full';
include F::config('appPath').'controller/scaffold_controller.php';