<?php
$tabLayout = array(
	'header' => '<h3>My Account</h3>',
	'style' => 'tabs',
	'nav' => array(
		array('name' => 'Update Profile', 'url' => F::url("{$fusebox->controller}.profile"), 'active' => F::is('*.profile,*.update_profile')),
		array('name' => 'Change Password', 'url' => F::url("{$fusebox->controller}.password"), 'active' => F::is('*.password,*.update_password')),
	),
);


// display tabs
ob_start();
include F::config('appPath').'app/view/global/tab.php';
$layout['content'] = ob_get_clean();


// wrap by global layout
$layout['width'] = 'full';
include F::config('appPath').'app/view/global/layout.php';