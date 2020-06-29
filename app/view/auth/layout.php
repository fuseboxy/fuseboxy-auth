<?php /*
<fusedoc>
	<io>
		<in />
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

// login form title
$customSettings  = F::appPath('view/auth/layout.settings.php');
$defaultSettings = F::appPath('view/auth/layout.settings.php-default');
include is_file($customSettings) ? $customSettings : $defaultSettings;

// flash
ob_start();
include F::appPath('view/global/layout.flash.php');
$authLayout['flash'] = trim( ob_get_clean() );

// display
ob_start();
include F::appPath('view/auth/form.php');
$layout['content'] = trim( ob_get_clean() );

// layout
include F::appPath('view/global/layout.html.php');