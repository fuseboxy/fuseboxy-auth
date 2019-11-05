<?php /*
<fusedoc>
	<io>
		<in>
			<string name="Framework::$mode" comments="check for unit test" />
		</in>
		<out>
			<structure name="$layout">
				<string name="metaTitle" comments="showing at browser tab" />
				<string name="content" />
			</structure>
			<structure name="$authLayout">
				<string name="flash" comments="success or failure message" />
				<string name="logo" optional="yes" />
				<string name="title" optional="yes" />
				<string name="brand" optional="yes" />
			</structure>
		</out>
	</io>
</fusedoc>
*/
$isUnitTest = ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST );


// settings
$layout['metaTitle'] = 'Admin Console';
$authLayout['logo']  = '';
$authLayout['title'] = 'Sign In';
$authLayout['brand'] = 'Admin Console';


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