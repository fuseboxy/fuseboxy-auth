<?php
// config
$tabLayout = array(
	'style' => 'tab',
	'position' => 'left',
	'header' => '<h3>User</h3>',
	'nav' => call_user_func(function(){
		$menus = array();
		// all existing roles
		$roles = R::getCol('SELECT DISTINCT role FROM user WHERE role != ? ORDER BY role ASC', array('SUPER'));
		if ( Auth::activeUserInRole('SUPER') ) array_unshift($roles, 'SUPER');
		// put into result
		foreach ( $roles as $item ) {
			$menus[] = array(
				'name' => ucwords( strtolower( $item ) ),
				'url' => F::url( F::command('controller').'&role='.$item ),
				'active' => ( isset($_SESSION['userController__userRole']) and $_SESSION['userController__userRole'] == $item ),
				'remark' => R::count('user', 'role = ? AND disabled = 0', array($item)),
			);
		}
		// done!
		return $menus;
	}),
);


// tab layout
ob_start();
include F::config('appPath').'view/global/tab.php';
$layout['content'] = ob_get_clean();


// global layout
include F::config('appPath').'view/global/layout.php';