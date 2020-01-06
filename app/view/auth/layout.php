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


// title
if ( file_exists(__DIR__.'/layout.settings.php') ) include 'layout.settings.php';


// flash
ob_start();
include F::config('appPath') . ( $isUnitTest ? '../test/utility-auth/view/flash.php' : 'view/global/layout.flash.php' );
$authLayout['flash'] = trim( ob_get_clean() );


// display
ob_start();
include F::config('appPath').'view/auth/panel.php';
$layout['content'] = trim( ob_get_clean() );


// layout
include F::config('appPath') . ( $isUnitTest ? '../test/utility-auth/view/layout.php' : 'view/global/layout.basic.php' );