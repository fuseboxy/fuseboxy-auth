<?php
class Auth {


	// define constant
	const SKIP_PASSWORD_CHECK = 1;


	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }


	// get information of simulated user or logged in user
	// ===> return sim user info when simulating
	// ===> otherwise, return logged in user
	public static function activeUser($key=null) {
		return Sim::user() ? Sim::user($key) : self::user($key);
	}


	// check whether active (sim > actual) user is specific group-role(s)
	public static function activeUserIn($permissions=array()) {
		return Sim::user() ? Sim::userIn($permissions) : self::userIn($permissions);
	}


	// check whether active (sim > actual) user is specific group(s)
	public static function activeUserInGroup($groups=array()) {
		return Sim::user() ? Sim::userInGroup($groups) : self::userInGroup($groups);
	}


	// check whether active (sim > actual) user is specific role(s)
	public static function activeUserInRole($roles=array()) {
		return Sim::user() ? Sim::userInRole($roles) : self::userInRole($roles);
	}


	// sign in user
	// ===> login by username or email
	public static function login($data, $mode=0) {
		// transform data (when necessary)
		if ( is_string($data) ) {
			$data = array('username' => $data);
		}
		// validation
		if ( !isset($data['username']) ) {
			self::$error = 'Username or email is required';
			return false;
		}
		if ( $mode != self::SKIP_PASSWORD_CHECK and !isset($data['password']) ) {
			self::$error = 'Password is required';
			return false;
		}
		// get user record
		$user = R::findOne('user', 'username = ? ', array($data['username']));
		if ( empty($user->id) ) $userByEmail = R::findOne('user', 'email = ? ', array($data['username']));
		// check user existence
		if ( empty($user->id) and empty($userByEmail->id) ) {
			self::$error = "User not found (username={$data['username']})";
			return false;
		}
		// check user status
		if ( !empty($user->disabled) or !empty($userByEmail->disabled) ) {
			self::$error = 'User account was disabled';
			if ( !empty($user->disabled) ) self::$error .= " (username={$data['username']})";
			if ( !empty($userByEmail->disabled) ) self::$error .= " (email={$data['username']})";
			return false;
		}
		// check password (case-sensitive)
		if ( $mode != self::SKIP_PASSWORD_CHECK and $user->password != $data['password'] ) {
			self::$error = 'Password is incorrect';
			return false;
		}
		// check captcha (when necessary)
		if ( class_exists('Captcha') and Captcha::validate() === false ) {
			self::$error = Captcha::error();
			return false;
		}
		// persist user info when succeed
		// ===> php does not allow storing bean (object) in session
		$_SESSION['auth_user'] = $user->export();
		// perform auto-login for {remember} days when session expired
		if ( isset($data['remember']) ) {
			if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
				$_COOKIE[self::__cookieKey()] = $user->username;
			} else {
				setcookie(self::__cookieKey(), $user->username, time()+intval($data['remember'])*24*60*60);
			}
		}
		// result
		return true;
	}


	// sign out user
	public static function logout() {
		$endSim = Sim::end();
		if ( $endSim === false ) {
			return false;
		}
		if ( isset($_SESSION['auth_user']) ) {
			unset($_SESSION['auth_user']);
		}
		if ( isset($_COOKIE[self::__cookieKey()]) ) {
			if ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) {
				unset($_COOKIE[self::__cookieKey()]);
			} else {
				setcookie(self::__cookieKey(), '', -1);
			}
		}
		return true;
	}


	// refresh session (usually use after profile update)
	public static function refresh() {
		$user = R::load('user', self::user('id'));
		$_SESSION['auth_user'] = $user->export();
		return true;
	}


	// get (actual) user information
	public static function user($key=null) {
		// auto-login (when remember login)
		if ( !isset($_SESSION['auth_user']) and isset($_COOKIE[self::__cookieKey()]) ) {
			$autoLoginResult = self::__autoLoginByCookie();
			if ( $autoLoginResult === false ) return false;
		}
		// return request info
		if ( empty($_SESSION['auth_user']) ) {
			return false;
		} elseif ( !isset($key) ) {
			return $_SESSION['auth_user'];
		} elseif ( isset($_SESSION['auth_user'][$key]) ) {
			return $_SESSION['auth_user'][$key];
		} else {
			return false;
		}
	}


	// check whether user (actual user by default) is in specific group-roles
	// ===> user-permission string is in {GROUP}.{ROLE} convention
	// ===> this function works for user assigned to single or multiple role(s)
	// ===> if no group specified, then just consider it as role (of all group)
	// ===> (case-insensitive)
	// ===> e.g. DEPT_A.ADMIN,DEPT_B.USER
	public static function userIn($queryPermissions=array(), $user=null) {
		// default checking against actual user
		if ( empty($user) ) {
			$user = self::user();
		}
		// turn argument into array if it is a comma-delimited list
		if ( is_string($queryPermissions) ) {
			$queryPermissions = explode(',', $queryPermissions);
		}
		// cleanse permission-to-check before comparison
		// ===> permission-to-check can have wildcard (e.g. *.ADMIN, DEPT_A.*)
		foreach ( $queryPermissions as $i => $groupAndRole ) {
			$groupAndRole = strtoupper($groupAndRole);
			$groupAndRole = explode('.', $groupAndRole);
			$groupAndRole = array_filter($groupAndRole);
			// consider last token as role
			// ===> if no group specified
			// ===> consider role of all group
			$role = array_pop($groupAndRole);
			$group = implode('.', $groupAndRole);
			if ( empty($group) ) $group = '*';
			$queryPermissions[$i] = "{$group}.{$role}";
		}
		// cleanse defined-user-permission and turn it into array
		// ===> user can be assign to multiple roles (comma-delimited)
		$actualPermissions = strtoupper($user['role']);
		$actualPermissions = explode(',', $actualPermissions);
		// compare permission-to-check against defined-user-permission
		foreach ( $queryPermissions as $queryGroupAndRole ) {
			$queryGroupAndRole = explode('.', $queryGroupAndRole);
			$queryRole = array_pop($queryGroupAndRole);
			$queryGroup = implode('.', $queryGroupAndRole);
			// go through each actual user-permissions
			// ===> quit immediately if there is match in both group and role
			foreach ( $actualPermissions as $actualGroupAndRole ) {
				$actualGroupAndRole = explode('.', $actualGroupAndRole);
				$actualRole = array_pop($actualGroupAndRole);
				$actualGroup = implode('.', $actualGroupAndRole);
				// compare...
				$isRoleMatch = ( $queryRole == $actualRole or $queryRole == '*' );
				$isGroupMatch = ( $queryGroup == $actualGroup or $queryGroup == '*' );
				if ( $isRoleMatch and $isGroupMatch ) {
					return true;
				}
			}
		}
		// no match...
		return false;
	}


	// check whether (actual) user is specific group(s) of any role
	public static function userInGroup($groups=array(), $user=null) {
		if ( empty($user) ) $user = self::user();
		if ( is_string($groups) ) $groups = explode(',', $groups);
		foreach ( $groups as $i => $val ) $groups[$i] = "{$val}.*";
		return self::userIn($groups, $user);
	}


	// check whether (actual) user is specific role(s) of any group
	public static function userInRole($roles=array(), $user=null) {
		if ( empty($user) ) $user = self::user();
		if ( is_string($roles) ) $roles = explode(',', $roles);
		foreach ( $roles as $i => $val ) $roles[$i] = "*.{$val}";
		return self::userIn($roles, $user);
	}


	// PRIVATE : auto-login user (when necessary)
	private static function __autoLoginByCookie() {
		$user = R::findOne('user', 'username = ? ', array($_COOKIE[self::__cookieKey()]));
		if ( empty($user->id) ) {
			self::$error = 'Auto-login failure';
			return false;
		}
		return self::login(array(
			'username' => $user->username,
			'password' => $user->password,
		));
	}


	// PRIVATE : cookie key
	// ===> cannot use session_name() to define property
	// ===> use function instead
	public static function __cookieKey() {
		return 'auth_user_'.session_name();
	}
	public static function cookieKey() {
		if ( Framework::$mode != Framework::FUSEBOX_UNIT_TEST ) {
			throw new Exception("Method Auth::cookieKey() is for unit test only");
			return false;
		}		
		return self::__cookieKey();
	}


}