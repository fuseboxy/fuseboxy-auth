<?php /*
<fusedoc>
	<io>
		<in>
			<string name="action" scope="$fusebox" />
			<boolean name="$hashPassword" scope="Auth" />
		</in>
		<out />
	</io>
</fusedoc>
*/
// capture original output
ob_start();
include F::appPath('view/scaffold/row.php');
$doc = Util::phpQuery( ob_get_clean() );


// when password hash
// ===> show custom message in listing
if ( F::is('*.index,*.row') and Auth::$hashPassword ) :
	$doc->find('div.col-password')->html('[PASSWORD HASHED]');
endif;


// display
echo $doc;