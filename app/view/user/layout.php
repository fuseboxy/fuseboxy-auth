<?php
// config
$tabLayout = array(
	'style' => 'tab',
	'position' => 'left',
	'header' => '<h3>User</h3>',
	'nav' => call_user_func(function(){
		$menus = array();
		// all existing roles
		$roles = ORM::query('SELECT DISTINCT role FROM user WHERE role != ? ORDER BY role ASC', ['SUPER'], 'col');
		if ( Auth::userInRole('SUPER') ) array_unshift($roles, 'SUPER');
		// put into result
		foreach ( $roles as $item ) {
			$menus[] = array(
				'name' => ucwords( strtolower( $item ) ),
				'url' => F::url( F::command('controller').'&role='.$item ),
				'active' => ( isset($_SESSION['userController__userRole']) and $_SESSION['userController__userRole'] == $item ),
				'remark' => ORM::count('user', 'role = ? AND disabled = 0', array($item)),
			);
		}
		// done!
		return $menus;
	}),
);


// tab layout
ob_start();
include F::appPath('view/tab/layout.php');
$layout['content'] = ob_get_clean();


// global layout
$layout['width'] = 'full';
include F::appPath('view/global/layout.php');