<?php
class Sim {


	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }


	// end user sim
	public static function end() {
		if ( isset($_SESSION['sim_user']) ) unset($_SESSION['sim_user']);
		return true;
	}


	// start user sim
	public static function start($user_id) {
		// get user info (treat argument as username if not numeric)
		if ( is_numeric($user_id) ) {
			$bean = ORM::get('user', $user_id);
		} else {
			$bean = ORM::first('user', 'username = ? ', array($user_id));
		}
		if ( $bean === false ) {
			self::$error = ORM::error();
			return false;
		}
		// start simulation
		// ===> php does not allow storing bean (object) in session
		$_SESSION['sim_user'] = $bean->export();
		// result
		return true;
	}


	// obtain specific information of simulated user
	// ===> return whole user structure if no variable name specified
	public static function user($key=null) {
		if ( empty($_SESSION['sim_user']) ) {
			return false;
		} elseif ( !isset($key) ) {
			return $_SESSION['sim_user'];
		} elseif ( isset($_SESSION['sim_user'][$key]) ) {
			return $_SESSION['sim_user'][$key];
		} else {
			return false;
		}
	}


	// check whether sim-user is specific group-roles
	public static function userIn($rights=array()) {
		return Auth::userIn($rights, self::user());
	}


	// check whether sim-user is specific groups
	public static function userInGroup($groups=array()) {
		return Auth::userInGroup($groups, self::user());
	}


	// check whether sim-user is specific roles
	public static function userInRole($roles=array()) {
		return Auth::userInRole($roles, self::user());
	}


}