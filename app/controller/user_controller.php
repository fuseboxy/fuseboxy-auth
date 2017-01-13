<?php
F::redirect('auth', !Auth::user());


// default role
if ( !isset($_SESSION['user_role']) ) {
	$_SESSION['user_role'] = R::getCell("SELECT role FROM user ORDER BY role ");
}


// change selected role (pass by url from layout)
if ( isset($arguments['role']) ) {
	$_SESSION['user_role'] = $arguments['role'];
}


// config
$scaffold = array(
	'beanType' => 'user',
	'editMode' => 'inline',
	'allowDelete' => Auth::activeUserInRole('SUPER'),
	'layoutPath' => F::config('appPath').'view/user/layout.php',
	'listFilter' => array('role = ?', array($_SESSION['user_role'])),
	'listOrder' => 'ORDER BY username',
	'listField' => array(
		'id' => '5%',
		'role|full_name' => '20%',
		'username|password' => '20%',
		'email|tel' => ''
	),
	'fieldConfig' => array(
		'id' => array(),
		'username' => array('placeholder' => 'Login'),
		'password' => array('placeholder' => 'Password'),
		'role' => array('default' => $_SESSION['user_role'], 'readonly' => !Auth::activeUserInRole('SUPER')),
		'full_name' => array('placeholder' => 'Full Name'),
		'email' => array('placeholder' => 'Email'),
		'tel' => array('placeholder' => 'Phone')
	)
);


// run!
$layout['width'] = 'full';
include 'scaffold_controller.php';