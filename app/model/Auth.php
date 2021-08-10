<?php
class Auth {


	// configurable settings
	public static $hashPassword = true;
	public static $resetPasswordFrom = 'noreply@example.com';
	public static $initUserDefaultPassword = '123456789';


	// define constant
	const SKIP_PASSWORD_CHECK = 'c';
	const SKIP_CAPTCHA_CHECK = 'p';
	const SKIP_ALL_CHECK = '';


	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }


	// get info or check permission of current user (sim > actual)
	// ===> more or less same as default behaviour of userXXX methods
	// ===> keep for backward compatibility
	public static function activeUser($key='')           { return self::user($key); }
	public static function activeUserIn($permissions='') { return self::userIn($permissions); }
	public static function activeUserInGroup($groups='') { return self::userInGroup($groups); }
	public static function activeUserInRole($roles='')   { return self::userInRole($roles); }




	/**
	<fusedoc>
		<description>
			get actual user information
		</description>
		<io>
			<in>
				<!-- cache -->
				<structure name="auth_user" scope="$_SESSION" optional="yes">
					<string name="~field~" />
				</structure>
				<!-- parameter -->
				<string name="$key" default="" />
			</in>
			<out>
				<!-- all -->
				<structure name="~return~" optional="yes" oncondition="when {key} is not defined">
					<number name="id" />
					<string name="role" />
					<string name="username" />
					<string name="password" />
					<string name="fullname" />
					<string name="email" />
					<string name="tel" />
				</structure>
				<!-- single field -->
				<string name="~return~" optional="yes" oncondition="when {key} is defined" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function actualUser($key='') {
		if ( empty($_SESSION['auth_user']) ) {
			return false;
		} elseif ( empty($key) ) {
			return $_SESSION['auth_user'];
		} elseif ( isset($_SESSION['auth_user'][$key]) ) {
			return $_SESSION['auth_user'][$key];
		} else {
			return false;
		}
	}
	// check permission of actual user
	public static function actualUserIn($permissions='') { return empty($_SESSION['auth_user']) ? false : self::userIn($permissions, $_SESSION['auth_user']); }
	public static function actualUserInGroup($groups='') { return empty($_SESSION['auth_user']) ? false : self::userInGroup($groups, $_SESSION['auth_user']); }
	public static function actualUserInRole($roles='')   { return empty($_SESSION['auth_user']) ? false : self::userInRole($roles, $_SESSION['auth_user']); }




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
				<string name="$initUserDefaultPassword" scope="self" />
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
		$userCount = ORM::count('user');
		if ( $userCount === false ) {
			self::$error = ORM::error();
			return false;
		} elseif ( $userCount ) {
			self::$error = 'User accounts already exist';
			return false;
		}
		// create default user
		$bean = ORM::new('user', array(
			'role'     => 'SUPER',
			'username' => 'developer',
			'password' => self::hashPassword( self::$initUserDefaultPassword ),
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
			'password' => self::$initUserDefaultPassword,
		);
		// write log (when necessary)
		if ( class_exists('Log') and Log::write([
			'username' => 'SYSTEM',
			'action'   => 'INIT_USER',
			'remark'   => $user,
		]) === false ) {
			self::$error = Log::error();
			return false;
		}
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
					<string name="username" comments="username or email" />
					<string name="password" />
				</structure>
				<list name="$check" optional="yes" default="c,p">
					<string name="+" comments="c=Captcha;p=Password" />
				</list>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function login($data, $check='c,p') {
		$hasError = false;
		// fix data (when necessary)
		if ( is_string($data) ) $data = array('username' => $data);
		if ( !is_array($check) ) $check = explode(',', $check);
		$check = array_map('strtolower', array_filter($check));
		// check captcha class exists
		if ( !class_exists('Captcha') and !empty(F::config('captcha')) ) {
			self::$error = 'Class [Captcha] is required';
			return false;
		}
		// validate captcha
		if ( in_array('c', $check) and !empty(F::config('captcha')) and Captcha::validate() === false ) {
			$hasError = Captcha::error();
		// check username exists
		} elseif ( empty($data['username']) ) {
			$hasError = 'Username or email is required';
		// check password exists
		} elseif ( in_array('p', $check) and empty($data['password']) ) {
			$hasError = 'Password is required';
		}
		// find user by username first
		if ( !$hasError ) {
			$user = ORM::first('user', 'username = ? ', array($data['username']));
			if ( $user === false ) $hasError = ORM::error();
		}
		// find user by email then
		if ( !$hasError and empty($user->id) ) {
			$user = ORM::first('user', 'email = ? ', array($data['username']));
			if ( $user === false ) $hasError = ORM::error();
		}
		// check user exists
		if ( !$hasError and empty($user->id) ) {
			$hasError = "User account <strong><em>{$data['username']}</em></strong> not found";
		}
		// check user status
		if ( !$hasError and $user->disabled ) {
			$field = ( $user->email == $data['username'] ) ? 'email' : 'username';
			$hasError = "User account was disabled ({$field}={$data['username']})";
		}
		// check password (case-sensitive)
		if ( !$hasError and in_array('p', $check) and $user->password != self::hashPassword($data['password']) ) {
			$hasError = 'Password is incorrect';
		}
		// persist user info as array (php could not store object into session)
		if ( !$hasError ) {
			$_SESSION['auth_user'] = Bean::export($user);
		}
		// write log (when necessary)
		if ( class_exists('Log') and Log::write([
			'action' => 'LOGIN',
			'remark' => !$hasError ? null : array(
				'FAILED',
				'username' => $data['username'],
				'ip'       => $_SERVER['REMOTE_ADDR'],
				'error'    => $hasError,
			),
		]) === false ) $hasError = Log::error();
		// check any error (after write log)
		if ( $hasError ) {
			self::$error = $hasError;
			return false;
		}
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			sign out user
		</description>
		<io>
			<in />
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function logout() {
		$username = self::actualUser('username');
		if ( $username === false ) return false;
		// clear sim user (when necessary)
		if ( class_exists('Sim') and Sim::end() === false ) {
			self::$error = Sim::error();
			return false;
		}
		// clear actual user
		if ( isset($_SESSION['auth_user']) ) unset($_SESSION['auth_user']);
		// write log (when necessary)
		if ( class_exists('Log') and Log::write([
			'action' => 'LOGOUT',
			'username' => $username
		]) === false ) {
			self::$error = Log::error();
			return false;
		}
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			refresh session of actual user
			===> usually being used after profile update
		</description>
		<io>
			<in>
				<structure name="auth_user" scope="$_SESSION" optional="yes">
					<number name="id" />
				</structure>
			</in>
			<out>
				<!-- cache -->
				<structure name="auth_user" scope="$_SESSION" />
				<!-- return -->
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function refresh() {
		// get latest data
		$id = self::actualUser('id');
		if ( $id === false ) return false;
		$user = ORM::get('user', $id);
		// validation
		if ( $user === false ) {
			self::$error = ORM::error();
			return false;
		}
		// persist data
		$_SESSION['auth_user'] = Bean::export($user);
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
		// check captcha library (when necessary)
		if ( !empty(F::config('captcha')) and !class_exists('Captcha') ) {
			self::$error = 'Class [Captcha] is required';
			return false;
		// validate captcha (when necessary)
		} elseif ( !empty(F::config('captcha')) and Captcha::validate() === false ) {
			self::$error = Captcha::error();
			return false;
		// check other library
		} elseif ( !class_exists('Util') ) {
			self::$error = 'Class [Util] is required';
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
		// save random password
		$user->password = self::hashPassword($random);
		$saveResult = ORM::save($user);
		if ( $saveResult === false ) {
			self::$error = ORM::error();
			return false;
		}
		// send mail (do not send when unit test)
		$mailResult = ( Framework::$mode == Framework::FUSEBOX_UNIT_TEST ) ? true : Util::sendMail(array(
			'from_name' => 'No Reply',
			'from' => self::$resetPasswordFrom,
			'to' => $user->email,
			'subject' => 'Your password has been reset successfully',
			'body' => 'New password: '.$random,
		));
		// write log (when necessary)
		if ( class_exists('Log') and Log::write([
			'action' => 'reset-password',
			'remark' => ( $mailResult === false ) ? Util::error() : '',
		]) === false ) {
			self::$error = Log::error();
			return false;
		}
		// notify mail error after writing log (when necessary)
		if ( $mailResult === false ) {
			self::$error = Util::error();
			return false;
		}
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			get (sim > actual) user information
		</description>
		<io>
			<in>
				<string name="$key" default="" />
			</in>
			<out>
				<!-- all -->
				<structure name="~return~" optional="yes" oncondition="when {key} is not defined">
					<number name="id" />
					<string name="role" />
					<string name="username" />
					<string name="password" />
					<string name="fullname" />
					<string name="email" />
					<string name="tel" />
				</structure>
				<!-- single field -->
				<string name="~return~" optional="yes" oncondition="when {key} is defined" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function user($key='') {
		return ( class_exists('Sim') and Sim::user() ) ? Sim::user($key) : self::actualUser($key);
	}




	/**
	<fusedoc>
		<description>
			check whether user is in specific group-roles
			===> user precedence is {args > sim > actual}
			===> user-permission string is in {GROUP}.{ROLE} convention (e.g. DEPT_A.ADMIN,DEPT_B.USER)
			===> this function works for user assigned to single or multiple role(s)
			===> if {role} field has no group specified, then just consider it as role (of all group)
			===> the checking is case-insensitive
		</description>
		<io>
			<in>
				<list name="permissions" delim="," example="DEPT_A.ADMIN,DEPT_B.USER,DEPT_C.*,*.GUEST">
					<string name="+" comments="~group~.~role~" />
				</list>
				<structure name="$user" optional="yes" default="sim > actual">
					<string name="role" comments="~group~.~role~" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function userIn($permissions='', $user=null) {
		// get user data
		if ( $user === null ) $user = ( class_exists('Sim') and Sim::user() ) ? Sim::user() : self::actualUser();
		// simply quit when not logged in
		if ( $user === false ) return false;
		// turn argument into array if it is a comma-delimited list
		if ( is_string($permissions) ) $permissions = explode(',', $permissions);
		// cleanse permission-to-check before comparison
		// ===> permission-to-check can have wildcard (e.g. *.ADMIN, DEPT_A.*)
		foreach ( $permissions as $i => $groupAndRole ) {
			$groupAndRole = strtoupper($groupAndRole);
			$groupAndRole = explode('.', $groupAndRole);
			$groupAndRole = array_filter($groupAndRole);
			// consider last token as role
			// ===> if no group specified
			// ===> consider role of all group
			$role = array_pop($groupAndRole);
			$group = implode('.', $groupAndRole);
			if ( empty($group) ) $group = '*';
			$permissions[$i] = "{$group}.{$role}";
		}
		// cleanse defined-user-permission and turn it into array
		// ===> user can be assign to multiple roles (comma-delimited)
		$actualPermissions = strtoupper($user['role']);
		$actualPermissions = explode(',', $actualPermissions);
		// compare permission-to-check against defined-user-permission
		foreach ( $permissions as $queryGroupAndRole ) {
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
				// simply quit when any match
				if ( $isRoleMatch and $isGroupMatch ) return true;
			}
		}
		// no match...
		return false;
	}




	/**
	<fusedoc>
		<description>
			check whether user is specific group(s) of any role
			===> user precedence is {args > sim > actual}
		</description>
		<io>
			<in>
				<list name="$groups" default="" example="DEPT_A,DEPT_B">
					<string name="*" />
				</list>
				<structure name="$user" optional="yes" default="sim > actual" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function userInGroup($groups='', $user=null) {
		// convert to {group.role} convention
		if ( is_string($groups) ) $groups = explode(',', $groups);
		foreach ( $groups as $key => $val ) $groups[$key] .= '.*';
		// done!
		return self::userIn($groups, $user);
	}




	/**
	<fusedoc>
		<description>
			check whether user is specific role(s) of any group
			===> user precedence is {args > sim > actual}
		</description>
		<io>
			<in>
				<list name="$roles" default="" example="ADMIN,USER">
					<string name="*" />
				</list>
				<structure name="$user" optional="yes" default="sim > actual" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function userInRole($roles='', $user=null) {
		// convert to {group.role} convention
		if ( is_string($roles) ) $roles = explode(',', $roles);
		foreach ( $roles as $key => $val ) $roles[$key] = '*.'.$val;
		// done!
		return self::userIn($roles, $user);
	}


} // class