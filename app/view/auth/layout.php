<?php
// flash
ob_start();
if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
	include F::config('appPath').'../test/utility-auth/view/flash.php';
} else {
	include F::config('appPath').'view/global/layout.flash.php';
}
$authLayout['flash'] = ob_get_clean();


// login box
$xfa['auth'] = 'auth';
ob_start();
$layout['panelTitle'] = 'Sign In';
$layout['panelSubtitle'] = 'Admin Console';
include F::config('appPath').'view/auth/panel.php';
$layout['content'] = ob_get_clean();


// layout
if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
	include F::config('appPath').'../test/utility-auth/view/layout.php';
} else {
	include F::config('appPath').'view/global/layout.basic.php';
}