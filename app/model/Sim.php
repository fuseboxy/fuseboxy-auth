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
	public static function start($userID) {
		// get user info (treat argument as username if not numeric)
		if ( is_numeric($userID) ) {
			$bean = ORM::get('user', $userID);
		} else {
			$bean = ORM::first('user', 'username = ? ', array($userID));
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
	public static function user($key='') {
		if ( empty($_SESSION['sim_user']) ) {
			return false;
		} elseif ( empty($key) ) {
			return $_SESSION['sim_user'];
		} elseif ( isset($_SESSION['sim_user'][$key]) ) {
			return $_SESSION['sim_user'][$key];
		} else {
			return false;
		}
	}


	// check whether sim-user is specific group-roles
	public static function userIn($permissions='') {
		return Auth::userIn($permissions, self::user());
	}


	// check whether sim-user is specific groups
	public static function userInGroup($groups='') {
		return Auth::userInGroup($groups, self::user());
	}


	// check whether sim-user is specific roles
	public static function userInRole($roles='') {
		return Auth::userInRole($roles, self::user());
	}


}