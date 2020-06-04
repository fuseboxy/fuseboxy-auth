<?php
class Auth {




	// configurable settings
	public static $hashPassword = true;
	public static $resetPasswordFrom = 'noreply@example.com';




	// define constant
	const NORMAL_PASSWORD_CHECK = 0;
	const HASHED_PASSWORD_CHECK = 1;
	const SKIP_PASSWORD_CHECK   = 2;
	const SKIP_ALL_CHECK        = 3;




	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }




	// get information of simulated user or logged in user
	// ===> return sim user info when simulating
	// ===> otherwise, return logged in user
	public static function activeUser($key=null) {
		return ( class_exists('Sim') and Sim::user() ) ? Sim::user($key) : self::user($key);
	}




	// check whether active (sim > actual) user is specific group-role(s)
	public static function activeUserIn($permissions=array()) {
		return ( class_exists('Sim') and Sim::user() ) ? Sim::userIn($permissions) : self::userIn($permissions);
	}




	// check whether active (sim > actual) user is specific group(s)
	public static function activeUserInGroup($groups=array()) {
		return ( class_exists('Sim') and Sim::user() ) ? Sim::userInGroup($groups) : self::userInGroup($groups);
	}




	// check whether active (sim > actual) user is specific role(s)
	public static function activeUserInRole($roles=array()) {
		return ( class_exists('Sim') and Sim::user() ) ? Sim::userInRole($roles) : self::userInRole($roles);
	}




	/**
	<fusedoc>
		<description>
			generate random password
		</description>
		<io>
			<in>
				<number name="length" default="8" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function generateRandomPassword($length=8) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*=-+:?";
		return substr(str_shuffle($chars), 0, $length);
	}




	/**
	<fusedoc>
		<description>
			perform password hashing when necessary
		</description>
		<io>
			<in>
				<boolean name="$hashPassword" scope="self" />
				<string name="$pwd" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	*/
	public static function hashPassword($pwd) {
		return self::$hashPassword ? hash('sha512', $pwd) : $pwd;
	}




	/**
	<fusedoc>
		<description>
			init first user account
		</description>
		<io>
			<in>
				<structure name="&$user" optional="yes" comments="pass by reference" />
			</in>
			<out>
				<boolean name="~return~" />
				<structure name="$user">
					<number name="id" />
					<string name="username" />
					<string name="password" />
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function initUser(&$user=null) {
		// validation
		if ( ORM::count('user') != 0 ) {
			self::$error = 'User accounts already exist';
			return false;
		}
		// create default user
		$bean = ORM::new('user', array(
			'role'     => 'SUPER',
			'username' => 'developer',
			'password' => self::hashPassword('123456789'),
			'disabled' => 0,
		));
		if ( $bean === false ) {
			self::$error = ORM::error();
			return false;
		}
		// save default user
		$id = ORM::save($bean);
		if ( $id === false ) {
			self::$error = 'Error occurred while creating first user account';
			return false;
		}
		// return extra info
		$user = array(
			'id' => $id,
			'role' => $bean->role,
			'username' => $bean->username,
			'password' => $defaultPassword,
		);
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			login user (by username or email)
		</description>
		<io>
			<in>
				<structure name="$data">
					<string name="username" />
					<string name="password" />
				</structure>
				<number name="$mode" optional="yes" default="~NORMAL_PASSWORD_CHECK~" />
			</in>
			<out>
				<string name="~self::__cookieKey()~" scope="$_COOKIE" />
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function login($data, $mode=0) {
		// check captcha (when necessary)
		if ( class_exists('Captcha') and Captcha::validate() === false and $mode != self::SKIP_ALL_CHECK ) {
			self::$error = Captcha::error();
			return false;
		}
		// transform data (when necessary)
		if ( is_string($data) ) {
			$data = array('username' => $data);
		}
		// validation
		if ( empty($data['username']) ) {
			self::$error = 'Username or email is required';
			return false;
		}
		if ( empty($data['password']) and $mode != self::SKIP_PASSWORD_CHECK and $mode != self::SKIP_ALL_CHECK ) {
			self::$error = 'Password is required';
			return false;
		}
		// get user record
		$user = ORM::first('user', 'username = ? ', array($data['username']));
		if ( empty($user->id) ) {
			$user = ORM::first('user', 'email = ? ', array($data['username']));
		}
		// check user existence
		if ( empty($user->id) ) {
			self::$error = "User account <strong><em>{$data['username']}</em></strong> not found";
			return false;
		}
		// check user status
		if ( !empty($user->disabled) ) {
			self::$error = 'User account was disabled';
			$field = ( $user->email == $data['username'] ) ? 'email' : 'username';
			self::$error .= " ({$field}={$data['username']})";
			return false;
		}
		// check password (case-sensitive)
		if ( false
			or ( $mode == self::HASHED_PASSWORD_CHECK and $user->password != $data['password'] )
			or ( $mode == self::NORMAL_PASSWORD_CHECK and $user->password != self::hashPassword($data['password']) )
		) {
			self::$error = 'Password is incorrect';
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
		// done!
		return true;
	}




	// sign out user
	public static function logout() {
		if ( class_exists('Sim') ) {
			$endSim = Sim::end();
			if ( $endSim === false ) return false;
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
		// get latest data
		$user = ORM::get('user', self::user('id'));
		if ( $user === false ) {
			self::$error = ORM::error();
			return false;
		}
		// persist data
		$_SESSION['auth_user'] = $user->export();
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			reset password and send email to user
		</description>
		<io>
			<in>
				<string name="$email" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function resetPassword($email) {
		$email = trim($email);
		// check captcha (when necessary)
		if ( class_exists('Captcha') and Captcha::validate() === false ) {
			self::$error = Captcha::error();
			return false;
		// check library
		} elseif ( !class_exists('Util') ) {
			self::$error = 'Util component is required';
			return false;
		// check email format
		} elseif ( empty($email) ) {
			self::$error = 'Email is required';
			return false;
		} elseif ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
			self::$error = 'Invalid email address';
			return false;
		}
		// check email existence
		$user = ORM::first('user', 'email = ?', array($email));
		if ( empty($user->id) ) {
			self::$error = "No user account is associated with <strong>{$email}</strong>";
			return false;
		} elseif ( !empty($user->disabled) ) {
			self::$error = "User account associated with <strong>{$email}</strong> was disabled";
			return false;
		}
		// generate random password
		$random = self::generateRandomPassword();
		// send mail (do not send when unit test)
		$mailResult = ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) ? true : Util::sendMail(array(
			'from_name' => 'No Reply',
			'from' => self::$resetPasswordFrom,
			'to' => $user->email,
			'subject' => 'Your password has been reset successfully',
			'body' => 'New password: '.$random,
		));
		if ( $mailResult === false ) {
			self::$error = Util::error();
			return false;
		}
		// save random password
		$user->password = self::hashPassword($random);
		$saveResult = ORM::save($user);
		if ( $saveResult === false ) {
			self::$error = ORM::error();
			return false;
		}
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			get (actual) user information
			===> auto-login is performed in this method
			===> because this method is used for login checking
		</description>
		<io>
			<in>
				<string name="~cookieKey~" scope="$_COOKIE" optional="yes" comments="for auto-login" />
				<structure name="auth_user" scope="$_SESSION" optional="yes">
					<string name="~field~" />
				</structure>
				<string name="$key" optional="yes" />
			</in>
			<out>
				<structure name="~return~" optional="yes" oncondition="when {key} is not defined" comments="all user fields" />
				<string name="~return~" optional="yes" oncondition="when {key} is defined" comments="specific user field" />
				<boolean name="~return~" value="false" optional="yes" oncondition="when failure : not logged in or {key} not found" />
			</out>
		</io>
	</fusedoc>
	*/
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




	/**
	<fusedoc>
		<description>
			check whether user (actual user by default) is in specific group-roles
			===> user-permission string is in {GROUP}.{ROLE} convention
			===> this function works for user assigned to single or multiple role(s)
			===> if no group specified, then just consider it as role (of all group)
			===> (case-insensitive)
			===> e.g. DEPT_A.ADMIN,DEPT_B.USER
		</description>
		<io>
			<in>
			</in>
			<out>
			</out>
		</io>
	</fusedoc>
	*/
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




	/**
	<fusedoc>
		<description>
			PRIVATE : auto-login user (when necessary)
		</description>
		<io>
			<in>
				<string name="~self::__cookieKey()~" scope="cookie" comments="username" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function __autoLoginByCookie() {
		$user = ORM::first('user', 'username = ? ', array($_COOKIE[self::__cookieKey()]));
		if ( empty($user->id) ) {
			self::$error = 'Auto-login failure';
			return false;
		}
		return self::login(array(
			'username' => $user->username,
			'password' => $user->password,
		), self::HASHED_PASSWORD_CHECK);
	}




	// PRIVATE : cookie key
	// ===> cannot use session_name() to define property
	// ===> use function instead
	private static function __cookieKey() {
		return 'auth_user_'.session_name();
	}
	public static function cookieKey() {
		if ( Framework::$mode != Framework::FUSEBOX_UNIT_TEST ) {
			throw new Exception("Method Auth::cookieKey() is for unit test only");
			return false;
		}		
		return self::__cookieKey();
	}




} // class