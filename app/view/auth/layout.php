<?php /*
<fusedoc>
	<io>
		<in>
			<string name="Framework::$mode" comments="check for unit test" />
		</in>
		<out>
			<structure name="$layout">
				<string name="content" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" comments="success or failure message" />
			</structure>
		</out>
	</io>
</fusedoc>
*/
$isUnitTest = ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST );


// login form title
$customSettings  = F::appPath('view/auth/layout.settings.php');
$defaultSettings = F::appPath('view/auth/layout.settings.php.DEFAULT');
include is_file($customSettings) ? $customSettings : $defaultSettings;


// flash
ob_start();
if ( $isUnitTest ) {
	include dirname(dirname(dirname(__DIR__))).'/test/utility-auth/view/flash.php';
} else {
	include F::appPath('view/global/layout.flash.php');
}
$authLayout['flash'] = trim( ob_get_clean() );


// display
ob_start();
include F::appPath('view/auth/panel.php');
$layout['content'] = trim( ob_get_clean() );


// layout
if ( $isUnitTest ) {
	include dirname(dirname(dirname(__DIR__))).'/test/utility-auth/view/layout.php';
} else {
	include F::appPath('view/global/layout.html.php');
}