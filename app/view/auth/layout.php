<?php
// flash
ob_start();
if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) {
	include F::config('appPath').'view/global/layout.flash.php';
} else {
	include F::config('appPath').'../test/utility-auth/flash.php';
}
$authLayout['flash'] = ob_get_clean();


// login box
ob_start();
$layout['panelTitle'] = 'Sign In<br /><small>Admin Console</small>';
include F::config('appPath').'view/auth/panel.php';
$layout['content'] = ob_get_clean();


// layout
if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) {
	include F::config('appPath').'view/global/layout.basic.php';
} else {
	include F::config('appPath').'../test/utility-auth/layout.php';
}