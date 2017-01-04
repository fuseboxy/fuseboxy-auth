<?php
// flash
ob_start();
if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
	include F::config('appPath').'../test/utility-auth/flash.php';
} else {
	include F::config('appPath').'view/global/layout.flash.php';
}
$authLayout['flash'] = ob_get_clean();


// login box
ob_start();
$layout['panelTitle'] = 'Sign In<br /><small>Admin Console</small>';
include F::config('appPath').'view/auth/panel.php';
$layout['content'] = ob_get_clean();


// layout
if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
	include F::config('appPath').'../test/utility-auth/layout.php';
} else {
	include F::config('appPath').'view/global/layout.basic.php';
}