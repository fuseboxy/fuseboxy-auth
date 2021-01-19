<?php
class Auth {


	// configurable settings
	public static $hashPassword = true;
	public static $resetPasswordFrom = 'noreply@example.com';
	public static $initUserDefaultPassword = '123456789';


	// define constant
	const NORMAL_PASSWORD_CHECK = 0;
	const HASHED_PASSWORD_CHECK = 1;
	const SKIP_PASSWORD_CHECK   = 2;
	const SKIP_ALL_CHECK        = 3;


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
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function login($data, $mode=0) {
		// check captcha library (when necessary)
		if ( !empty(F::config('captcha')) and !class_exists('Captcha') ) {
			self::$error = 'Class [Captcha] is required';
			return false;
		// validate captcha (when necessary)
		} elseif ( $mode != self::SKIP_ALL_CHECK and !empty(F::config('captcha')) and Captcha::validate() === false ) {
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
		// get user by username first
		$user = ORM::first('user', 'username = ? ', array($data['username']));
		if ( $user === false ) {
			self::$error = ORM::error();
			return false;
		}
		// then get user by email (when necessary)
		if ( empty($user->id) ) {
			$user = ORM::first('user', 'email = ? ', array($data['username']));
			if ( $user === false ) {
				self::$error = ORM::error();
				return false;
			}
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
		if ( ORM::vendor() == 'redbean' ) $_SESSION['auth_user'] = $user->export();
		else $_SESSION['auth_user'] = get_object_vars($user);
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
		// clear sim user
		if ( class_exists('Sim') ) {
			$endSim = Sim::end();
			if ( $endSim === false ) {
				self::$error = Sim::error();
				return false;
			}
		}
		// clear actual user
		if ( isset($_SESSION['auth_user']) ) {
			unset($_SESSION['auth_user']);
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
		if ( ORM::vendor() == 'redbean' ) $_SESSION['auth_user'] = $user->export();
		else $_SESSION['auth_user'] = get_object_vars($user);
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
		if ( empty($user) and class_exists('Sim') and Sim::user() ) {
			$user = Sim::user();
		} elseif ( empty($user) ) {
			$user = self::actualUser();
		}
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
		// get user data
		if ( empty($user) and class_exists('Sim') and Sim::user() ) {
			$user = Sim::user();
		} elseif ( empty($user) ) {
			$user = self::actualUser();
		}
		// turn into {group.role} convention
		if ( is_string($groups) ) $groups = explode(',', $groups);
		foreach ( $groups as $key => $val ) $groups[$key] .= '.*';
		// reuse method
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
		// get user data
		if ( empty($user) and class_exists('Sim') and Sim::user() ) {
			$user = Sim::user();
		} elseif ( empty($user) ) {
			$user = self::actualUser();
		}
		// turn into {group.role} convention
		if ( is_string($roles) ) $roles = explode(',', $roles);
		foreach ( $roles as $key => $val ) $roles[$key] = '*.'.$val;
		// reuse method
		return self::userIn($roles, $user);
	}


} // class