<?php
// tab layout config
$tabLayout = array(
	'style' => 'tab',
	'position' => 'left',
	'header' => '<h3>User</h3>',
	'nav' => array(),
);


// tab : all existing types
$roles = R::getCol("SELECT DISTINCT role FROM user " . ( !Auth::activeUserInRole('SUPER') ? " WHERE role != 'SUPER' " : "" ) );
foreach ( $roles as $t ) {
	$tabLayout['nav'][] = array(
		'name' => ucwords( strtolower( $t ) ),
		'url' => F::url("{$fusebox->controller}&role={$t}"),
		'active' => ( isset($_SESSION['user_role']) and $_SESSION['user_role'] == $t )
	);
}


// tab layout
ob_start();
include F::config('appPath').'view/global/tab.php';
$layout['content'] = ob_get_clean();


// wrap by global layout
include F::config('appPath').'view/global/layout.php';