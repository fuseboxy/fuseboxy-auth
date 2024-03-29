<?php
// config
$tabLayout = array(
	'header' => 'My Account',
	'style' => 'tabs',
	'nav' => array(
		array('name' => 'Update Profile', 'url' => F::url("{$fusebox->controller}.profile"), 'active' => F::is('*.profile,*.update_profile')),
		array('name' => 'Change Password', 'url' => F::url("{$fusebox->controller}.password"), 'active' => F::is('*.password,*.update_password')),
	),
);


// tab layout
ob_start();
include F::appPath('view/tab/layout.php');
$layout['content'] = ob_get_clean();


// global layout
$layout['width'] = 'full';
include F::appPath('view/global/layout.php');