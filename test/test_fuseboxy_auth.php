<?php
class TestFuseboxyAuth extends UnitTestCase {


	function __construct(){
		$GLOBALS['FUSEBOX_UNIT_TEST'] = true;
		if ( !class_exists('Framework') ) {
			include dirname(__FILE__).'/utility-auth/framework/1.0/fuseboxy.php';
		}
		if ( !class_exists('F') ) {
			include dirname(__FILE__).'/utility-auth/framework/1.0/F.php';
		}
		if ( !class_exists('Auth') ) {
			include dirname(dirname(__FILE__)).'/app/model/Auth.php';
		}
		if ( !class_exists('Sim') ) {
			include dirname(dirname(__FILE__)).'/app/model/Sim.php';
		}
		if ( !class_exists('R') ) {
			include dirname(__FILE__).'/utility-auth/redbeanphp/4.3.3/rb.php';
			include dirname(__FILE__).'/utility-auth/config/rb_config.php';
		}
	}


	function test__Auth__activeUser(){
		// active user : no sim
		$_SESSION['auth_user'] = array('username' => 'foo');
		$this->assertTrue ( Auth::activeUser('username') == Auth::user('username') );
		$this->assertFalse( Auth::activeUser('username') == Sim::user('username') );
		// active user : has sim
		$_SESSION['sim_user'] = array('username' => 'bar');
		$this->assertFalse( Auth::activeUser('username') == Auth::user('username') );
		$this->assertTrue ( Auth::activeUser('username') == Sim::user('username') );
		// clean-up
		unset($_SESSION['auth_user'], $_SESSION['sim_user']);
	}


	function test__Auth__activeUserIn(){
		$_SESSION['auth_user'] = array('role' => 'DEPT_A.ADMIN,DEPT_B.USER,DEPT_C.GUEST');
		// without user-sim
		$this->assertTrue ( Auth::activeUserIn('DEPT_A.*') );
		$this->assertFalse( Auth::activeUserIn('DEPT_X.*') );
		$this->assertTrue ( Auth::activeUserIn('*.USER') );
		$this->assertFalse( Auth::activeUserIn('*.SUPER_USER') );
		$this->assertTrue ( Auth::activeUserIn('DEPT_C.GUEST,FOO.BAR') );
		$this->assertFalse( Auth::activeUserIn('DEPT_Z.ULTIMATE_USER,FOO.BAR') );
		// with user-sim
		$_SESSION['sim_user'] = array('role' => 'DEPT_X.POWER_USER,DEPT_Y.SUPER_USER,DEPT_Z.ULTIMATE_USER');
		$this->assertFalse( Auth::activeUserIn('DEPT_A.*') );
		$this->assertTrue ( Auth::activeUserIn('DEPT_X.*') );
		$this->assertFalse( Auth::activeUserIn('*.USER') );
		$this->assertTrue ( Auth::activeUserIn('*.SUPER_USER') );
		$this->assertFalse( Auth::activeUserIn('DEPT_C.GUEST,FOO.BAR') );
		$this->assertTrue ( Auth::activeUserIn('DEPT_Z.ULTIMATE_USER,FOO.BAR') );
		// clean-up
		unset($_SESSION['auth_user'], $_SESSION['sim_user']);
	}


	function test__Auth__activeUserInGroup(){
		$_SESSION['auth_user'] = array('role' => 'HKU.PROFESSOR,HKU.LECTURER,HKU.STAFF');
		// without user-sim
		$this->assertTrue ( Auth::activeUserInGroup('*') );
		$this->assertTrue ( Auth::activeUserInGroup('HKU') );
		$this->assertFalse( Auth::activeUserInGroup('CITYU') );
		$this->assertTrue ( Auth::activeUserInGroup('HKU,UST') );
		$this->assertFalse( Auth::activeUserInGroup('CITYU,UST') );
		$this->assertTrue ( Auth::activeUserInGroup('HKU,CITYU') );
		$this->assertTrue ( Auth::activeUserInGroup('*,UST') );
		// with user-sim
		$_SESSION['sim_user'] = array('role' => 'CITYU.STUDENT,CITYU.PARTTIME_STUDENT,CITYU.ALUMNI');
		$this->assertTrue ( Auth::activeUserInGroup('*') );
		$this->assertFalse( Auth::activeUserInGroup('HKU') );
		$this->assertTrue ( Auth::activeUserInGroup('CITYU') );
		$this->assertFalse( Auth::activeUserInGroup('HKU,UST') );
		$this->assertTrue ( Auth::activeUserInGroup('CITYU,UST') );
		$this->assertTrue ( Auth::activeUserInGroup('HKU,CITYU') );
		$this->assertTrue ( Auth::activeUserInGroup('*,UST') );
		// clean-up
		unset($_SESSION['auth_user'], $_SESSION['sim_user']);
	}


	function test__Auth__activeUserInRole(){
		$_SESSION['auth_user'] = array('role' => 'HKU.PROFESSOR,HKU.LECTURER,HKU.STAFF');
		// without user-sim
		$this->assertTrue ( Auth::activeUserInRole('*') );
		$this->assertTrue ( Auth::activeUserInRole('PROFESSOR') );
		$this->assertFalse( Auth::activeUserInRole('STUDENT') );
		$this->assertTrue ( Auth::activeUserInRole('LECTURER,STAFF') );
		$this->assertFalse( Auth::activeUserInRole('STUDENT,ALUMNI') );
		$this->assertTrue ( Auth::activeUserInRole('STAFF,STUDENT') );
		$this->assertTrue ( Auth::activeUserInRole('*,GUEST') );
		// with user-sim
		$_SESSION['sim_user'] = array('role' => 'CITYU.STUDENT,CITYU.PARTTIME_STUDENT,CITYU.ALUMNI');
		$this->assertTrue ( Auth::activeUserInRole('*') );
		$this->assertFalse( Auth::activeUserInRole('PROFESSOR') );
		$this->assertTrue ( Auth::activeUserInRole('STUDENT') );
		$this->assertFalse( Auth::activeUserInRole('LECTURER,STAFF') );
		$this->assertTrue ( Auth::activeUserInRole('STUDENT,ALUMNI') );
		$this->assertTrue ( Auth::activeUserInRole('STAFF,STUDENT') );
		$this->assertTrue ( Auth::activeUserInRole('*,GUEST') );
		// clean-up
		unset($_SESSION['auth_user'], $_SESSION['sim_user']);
	}


	function test__Auth__login(){
		// create dummy record
		$bean = R::dispense('user');
		$bean->username = 'foo';
		$bean->password = 'bar';
		$bean->email = 'foo@bar.com';
		$bean->name = 'Foo Bar';
		$bean->disabled = 0;
		$id = R::store($bean);
		$this->assertTrue($id);
		// login with username & password
		$loginResult = Auth::login(array(
			'username' => 'foo',
			'password' => 'bar',
		));
		$this->assertTrue( $loginResult );
		$this->assertTrue( Auth::user() );
		$this->assertTrue( isset($_SESSION['auth_user']) );
		$this->assertTrue( Auth::user('username') === 'foo' );
		Auth::logout();
		// login with email & password
		$loginResult = Auth::login(array(
			'email' => 'foo@bar.com',
			'password' => 'bar',
		));
		$this->assertTrue( $loginResult );
		$this->assertTrue( Auth::user() );
		$this->assertTrue( Auth::user('username') === 'foo' );
		Auth::logout();
		// login with password of uppercase (should be case-sensitive)
		$loginResult = Auth::login(array(
			'username' => 'foo',
			'password' => 'BAR',
		));
		$this->assertFalse( $loginResult );
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/wrong password/i', Auth::error());
		Auth::logout();
		// login with username or email missing
		$loginResult = Auth::login(array(
			'password' => 'bar',
		));
		$this->assertFalse( $loginResult );
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/username or email is required/i', Auth::error());
		Auth::logout();
		// login with password missing
		$loginResult = Auth::login(array(
			'username' => 'foo'
		));
		$this->assertFalse( $loginResult );
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/password is required/i', Auth::error());
		Auth::logout();
		// login with password legitimately skipped
		$loginResult = Auth::login(array( 'username' => 'foo' ), true);
		$this->assertTrue( $loginResult );
		$this->assertTrue( Auth::user() );
		$this->assertTrue( Auth::user('username') == 'foo' );
		Auth::logout();
		$loginResult = Auth::login(array( 'email' => 'foo@bar.com' ), true);
		$this->assertTrue( $loginResult );
		$this->assertTrue( Auth::user() );
		$this->assertTrue( Auth::user('username') == 'foo' );
		Auth::logout();
		// invalid login
		$loginResult = Auth::login(array(
			'username' => 'abcde',
			'password' => '12345',
		));
		$this->assertFalse( $loginResult );
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/user record not found/i', Auth::error());
		Auth::logout();
		// account disabled
		$bean = R::findOne('user', 'username = ?', array('foo'));
		$bean->disabled = 1;
		R::store($bean);
		$loginResult = Auth::login(array(
			'username' => 'foo',
			'password' => 'bar',
		));
		$this->assertFalse( $loginResult );
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/user account was disabled/i', Auth::error());
		Auth::logout();
		$bean->disabled = 0;
		R::store($bean);
		// remember login
		$loginResult = Auth::login(array(
			'username' => 'foo',
			'password' => 'bar',
			'remember' => 365,
		));
		$this->assertTrue( $loginResult );
		$this->assertTrue( isset($_COOKIE[Auth::cookieKey()]) );
		Auth::logout();
		// check auto-login (by cookie)
		$_COOKIE[Auth::cookieKey()] = 'foobar';
		$this->assertFalse( Auth::user() );
		$this->assertPattern('/auto-login failure/i', Auth::error());
		$_COOKIE[Auth::cookieKey()] = 'foo';
		$this->assertTrue( Auth::user() );
		Auth::logout();
		// clean-up
		R::nuke();
	}


	function test__Auth__logout(){
		// create dummy records
		$firstBean = R::dispense('user');
		$firstBean->import(array(
			'username' => 'foobar',
			'password' => '123456',
		));
		$firstId = R::store($firstBean);
		$this->assertTrue( $firstId );
		$secondBean = R::dispense('user');
		$secondBean->import(array(
			'username' => 'abcxyz',
			'password' => '999999',
		));
		$secondId = R::store($secondBean);
		$this->assertTrue( $secondId );
		// user login and start user-sim
		$this->assertTrue( Auth::login($firstBean->export()) );
		$this->assertTrue( Sim::start($secondId) );
		// check both actual user and simulated user
		$this->assertTrue( Auth::logout() );
		$this->assertFalse( Auth::user() );
		$this->assertFalse( Sim::user() );
		// check cookie clearance
		$_COOKIE[Auth::cookieKey()] = 'foobar';
		$this->assertTrue( Auth::logout() );
		$this->assertTrue( !isset($_COOKIE[Auth::cookieKey()]) );
		// clean-up
		R::nuke();
	}


	function test__Auth__refresh(){
		// create dummy record
		$bean = R::dispense('user');
		$bean->import(array(
			'username' => 'foobar',
			'name' => 'Foo Bar',
			'email' => 'foo@bar.com',
		));
		$id = R::store($bean);
		$this->assertTrue($id);
		// login before update
		$this->assertTrue( Auth::login(array('username' => 'foobar'), true) );
		// update record
		$bean = R::load('user', $id);
		$bean->name = 'Unit Test';
		$bean->email = 'unit@test.net';
		R::store($bean);
		// check value before refresh
		$this->assertTrue( Auth::user('username') == 'foobar' );
		$this->assertTrue( Auth::user('name') == 'Foo Bar' );
		$this->assertTrue( Auth::user('email') == 'foo@bar.com' );
		// check value after refresh
		$this->assertTrue( Auth::refresh() );
		$this->assertTrue( Auth::user('username') == 'foobar' );
		$this->assertTrue( Auth::user('name') == 'Unit Test' );
		$this->assertTrue( Auth::user('email') == 'unit@test.net' );
		// clean-up
		Auth::logout();
		R::nuke();
	}


	function test__Auth__user(){
		$_SESSION['auth_user'] = array('username' => 'foobar', 'email' => 'foobar@unit.test');
		// no key
		$this->assertTrue( is_array(Auth::user()) );
		// existing key
		$this->assertTrue( Auth::user('username') == 'foobar' );
		// non-existing key
		$this->assertFalse( Auth::user('password') );
		// not login
		unset($_SESSION['auth_user']);
		$this->assertFalse( Auth::user() );
	}


	function test__Auth__userIn(){
		// role : single-single
		$_SESSION['auth_user'] = array('role' => 'ADMIN');
		$this->assertTrue( Auth::userIn('*') );
		$this->assertTrue( Auth::userIn('ADMIN') );  // [user = ADMIN / check = ADMIN]
		$this->assertFalse( Auth::userIn('USER') );  // [user = ADMIN / check = USER]
		unset($_SESSION['auth_user']);
		// role : single-multi
		$_SESSION['auth_user'] = array('role' => 'ADMIN');
		$this->assertTrue( Auth::userIn('*,FOO') );
		$this->assertTrue( Auth::userIn('SUPER,ADMIN') );  // [user = ADMIN / check = SUPER,ADMIN]
		$this->assertFalse( Auth::userIn('SUPER,USER') );  // [user = ADMIN / check = SUPER,USER]
		unset($_SESSION['auth_user']);
		// role : multi-single
		$_SESSION['auth_user'] = array('role' => 'SUPER,ADMIN');
		$this->assertTrue( Auth::userIn('*') );
		$this->assertTrue( Auth::userIn('SUPER') );  // [user = SUPER,ADMIN / check = SUPER]
		$this->assertFalse( Auth::userIn('USER') );  // [user = SUPER,ADMIN / check = USER]
		unset($_SESSION['auth_user']);
		// role : multi-multi
		$_SESSION['auth_user'] = array('role' => 'SUPER,ADMIN');
		$this->assertTrue( Auth::userIn('*,FOO') );
		$this->assertTrue( Auth::userIn('SUPER,USER,GUEST') );       // [user = SUPER,ADMIN / check = SUPER,USER,GUEST]
		$this->assertFalse( Auth::userIn('USER,GUEST,ANONYMOUS') );  // [user = SUPER,ADMIN / check = USER,GUEST,ANONYMOUS]
		unset($_SESSION['auth_user']);
		// group.role : single-single
		$_SESSION['auth_user'] = array('role' => 'A.USER');
		$this->assertTrue( Auth::userIn('*.*') );
		$this->assertTrue( Auth::userIn('*.USER') );    // [user = A.USER / check = *.USER]
		$this->assertTrue( Auth::userIn('A.*') );       // [user = A.USER / check = A.*]
		$this->assertTrue( Auth::userIn('A.USER') );    // [user = A.USER / check = A.USER]
		$this->assertFalse( Auth::userIn('*.ADMIN') );  // [user = A.USER / check = *.ADMIN]
		$this->assertFalse( Auth::userIn('B.*') );      // [user = A.USER / check = B.*]
		$this->assertFalse( Auth::userIn('B.GUEST') );  // [user = A.USER / check = B.GUEST]
		unset($_SESSION['auth_user']);
		// group.role : single-multi
		$_SESSION['auth_user'] = array('role' => 'A.USER');
		$this->assertTrue( Auth::userIn('*.*,FOO.BAR') );
		$this->assertTrue( Auth::userIn('A.USER,B.SUPER') );    // [user = A.USER / check = A.USER,B.SUPER]
		$this->assertTrue( Auth::userIn('A.*,B.*') );           // [user = A.USER / check = A.*,B.*]
		$this->assertTrue( Auth::userIn('*.USER,*.SUPER') );    // [user = A.USER / check = *.USER,*.SUPER]
		$this->assertFalse( Auth::userIn('A.ADMIN,B.SUPER') );  // [user = A.USER / check = A.ADMIN,B.SUPER]
		$this->assertFalse( Auth::userIn('B.*,C.*') );          // [user = A.USER / check = B.*,C.*]
		$this->assertFalse( Auth::userIn('*.ADMIN,*.SUPER') );  // [user = A.USER / check = *.ADMIN,*.SUPER]
		unset($_SESSION['auth_user']);
		// group.role : multi-single
		$_SESSION['auth_user'] = array('role' => 'A.USER,B.ADMIN');
		$this->assertTrue( Auth::userIn('*.*') );
		$this->assertTrue( Auth::userIn('A.*') );       // [user = A.USER,B.ADMIN / check = A.*]
		$this->assertTrue( Auth::userIn('*.USER') );    // [user = A.USER,B.ADMIN / check = *.USER]
		$this->assertTrue( Auth::userIn('*.ADMIN') );   // [user = A.USER,B.ADMIN / check = *.ADMIN]
		$this->assertFalse( Auth::userIn('C.USER') );   // [user = A.USER,B.ADMIN / check = C.USER]
		$this->assertFalse( Auth::userIn('C.*') );      // [user = A.USER,B.ADMIN / check = C.*]
		$this->assertFalse( Auth::userIn('*.SUPER') );  // [user = A.USER,B.ADMIN / check = *.SUPER]
		unset($_SESSION['auth_user']);
		// group.role : multi-multi
		$_SESSION['auth_user'] = array('role' => 'A.USER,B.ADMIN');
		$this->assertTrue( Auth::userIn('*.*,FOO.BAR') );
		$this->assertTrue( Auth::userIn('A.GUEST,B.ADMIN') );  // [user = A.USER,B.ADMIN / check = A.GUEST,B.ADMIN]
		$this->assertTrue( Auth::userIn('A.USER,B.USER') );    // [user = A.USER,B.ADMIN / check = A.USER,B.USER]
		$this->assertTrue( Auth::userIn('A.USER,B.ADMIN') );   // [user = A.USER,B.ADMIN / check = A.USER,B.ADMIN]
		$this->assertTrue( Auth::userIn('*.USER,B.GUEST') );   // [user = A.USER,B.ADMIN / check = *.USER,B.GUEST]
		$this->assertFalse( Auth::userIn('A.GUEST,B.USER') );  // [user = A.USER,B.ADMIN / check = A.GUEST,B.USER]
		$this->assertFalse( Auth::userIn('C.*,*.GUEST') );     // [user = A.USER,B.ADMIN / check = C.*,*.GUEST]
		unset($_SESSION['auth_user']);
	}


	function test__Auth__userInGroup(){
		// single-single
		$_SESSION['auth_user'] = array('role' => 'TEAM_A.ADMIN');
		$this->assertTrue( Auth::userInGroup('TEAM_A') );
		$this->assertTrue( Auth::userInGroup('*') );
		$this->assertFalse( Auth::userInGroup('TEAM_X') );
		// multi-single
		$_SESSION['auth_user'] = array('role' => 'TEAM_A.USER,TEAM_B.ADMIN');
		$this->assertTrue( Auth::userInGroup('TEAM_A') );
		$this->assertTrue( Auth::userInGroup('*') );
		$this->assertFalse( Auth::userInGroup('TEAM_X') );
		// single-multi
		$_SESSION['auth_user'] = array('role' => 'TEAM_C.GUEST');
		$this->assertTrue( Auth::userInGroup('TEAM_A,TEAM_B,TEAM_C') );
		$this->assertFalse( Auth::userInGroup('TEAM_X,TEAM_Y') );
		$this->assertTrue( Auth::userInGroup('TEAM_X,TEAM_Y,*') );
		// multi-multi
		$_SESSION['auth_user'] = array('role' => 'TEAM_A.USER,TEAM_B.ADMIN,TEAM_C.GUEST');
		$this->assertTrue( Auth::userInGroup('TEAM_A,TEAM_B,TEAM_C') );
		$this->assertTrue( Auth::userInGroup('TEAM_X,TEAM_A') );
		$this->assertFalse( Auth::userInGroup('TEAM_X,TEAM_Y,TEAM_Z') );
		$this->assertTrue( Auth::userInGroup('TEAM_X,*') );
		// clean-up
		unset($_SESSION['auth_user']);
	}


	function test__Auth__userInRole(){
		// single-single
		$_SESSION['auth_user'] = array('role' => 'DEPT_A.ADMIN');
		$this->assertTrue( Auth::userInRole('*') );
		$this->assertTrue( Auth::userInRole('ADMIN') );
		$this->assertFalse( Auth::userInRole('GUEST') );
		// multi-single
		$_SESSION['auth_user'] = array('role' => 'DEPT_A.USER,DEPT_B.ADMIN');
		$this->assertTrue( Auth::userInRole('*') );
		$this->assertTrue( Auth::userInRole('USER') );
		$this->assertFalse( Auth::userInRole('GUEST') );
		// single-multi
		$_SESSION['auth_user'] = array('role' => 'DEPT_C.GUEST');
		$this->assertTrue( Auth::userInRole('ADMIN,GUEST') );
		$this->assertFalse( Auth::userInRole('USER,POWER_USER') );
		$this->assertTrue( Auth::userInRole('USER,POWER_USER,*') );
		// multi-multi
		$_SESSION['auth_user'] = array('role' => 'DEPT_A.USER,DEPT_B.ADMIN,DEPT_C.GUEST');
		$this->assertTrue( Auth::userInRole('USER,ADMIN,GUEST') );
		$this->assertTrue( Auth::userInRole('POWER_USER,USER') );
		$this->assertFalse( Auth::userInRole('POWER_USER,SUPER_USER,ULTIMATE_USER') );
		$this->assertTrue( Auth::userInRole('POWER_USER,*') );
		// clean-up
		unset($_SESSION['auth_user']);
	}


	function test__Sim__end(){
		// with session available
		$_SESSION['sim_user'] = array('foo' => 'bar');
		$endResult = Sim::end();
		$this->assertTrue( $endResult );
		$this->assertFalse( Sim::user() );
		$this->assertTrue( !isset($_SESSION['sim_user']) );
		// without session available
		$endResult = Sim::end();
		$this->assertTrue( $endResult );  // do not consider as error even no session
	}


	function test__Sim__start(){
		// without anyone specified
		$startResult = Sim::start();
		$this->assertFalse( $startResult );
		$this->assertFalse( Sim::user() );
		$this->assertPattern("/argument \[user_id\] is required/i", Sim::error());
		Sim::end();
		// with non-existing user specified
		$startResult = Sim::start(999999);
		$this->assertFalse( $startResult );
		$this->assertFalse( Sim::user() );
		$this->assertPattern("/not found/i", Sim::error());
		Sim::end();
		// with existing user specified
		$bean = R::dispense('user');
		$bean->username = 'foo';
		$bean->password = 'bar';
		$id = R::store($bean);
		$this->assertTrue( !empty($id) );
		$startResult = Sim::start($id);
		$this->assertTrue( Sim::user() );
		$this->assertTrue( isset($_SESSION['sim_user']) );
		Sim::end();
		// clean-up
		R::nuke();
	}


	function test__Sim__user(){
		$_SESSION['sim_user'] = array('id' => 1, 'login' => 'foobar', 'name' => 'Foo Bar');
		// no key
		$this->assertTrue( Sim::user() );
		$this->assertTrue( is_array(Sim::user()) );
		// existing key
		$this->assertTrue( Sim::user('login') == 'foobar' );
		// non-existing key
		$this->assertFalse( Sim::user('email') );
		// user-sim not started
		unset($_SESSION['sim_user']);
		$this->assertFalse( Sim::user() );
	}


	function test__Sim__userIn(){
		$_SESSION['sim_user'] = array('role' => 'CITYU.STAFF,CITYU.STUDENT,CITYU.ALUMNI');
		// check
		$this->assertTrue ( Sim::userIn('*.*') );
		$this->assertFalse( Sim::userIn('HKU.*') );
		$this->assertTrue ( Sim::userIn('CITYU.*') );
		$this->assertFalse( Sim::userIn('*.LECTURER') );
		$this->assertTrue ( Sim::userIn('*.STAFF') );
		$this->assertFalse( Sim::userIn('HKU.STAFF') );
		$this->assertTrue ( Sim::userIn('HKU.STAFF,*.*') );
		$this->assertTrue ( Sim::userIn('HKU.STAFF,CITYU.STUDENT') );
		// clean-up
		unset($_SESSION['sim_user']);
	}


	function test__Sim__userInGroup(){
		$_SESSION['sim_user'] = array('role' => 'CITYU.STAFF,CITYU.STUDENT,CITYU.ALUMNI');
		// check
		$this->assertTrue ( Sim::userInGroup('*') );
		$this->assertTrue ( Sim::userInGroup('CITYU') );
		$this->assertFalse( Sim::userInGroup('HKU') );
		$this->assertFalse( Sim::userInGroup('HKU,UST') );
		$this->assertTrue ( Sim::userInGroup('CITYU,UST') );
		$this->assertTrue ( Sim::userInGroup('HKU,UST,*') );
		// clean-up
		unset($_SESSION['sim_user']);
	}


	function test__Sim__userInRole(){
		$_SESSION['sim_user'] = array('role' => 'CITYU.STAFF,CITYU.STUDENT,CITYU.ALUMNI');
		// check
		$this->assertTrue ( Sim::userInRole('*') );
		$this->assertTrue ( Sim::userInRole('STAFF') );
		$this->assertFalse( Sim::userInRole('LECTURER') );
		$this->assertFalse( Sim::userInRole('LECTURER,PROFESSOR') );
		$this->assertTrue ( Sim::userInRole('STAFF,LECTURER') );
		$this->assertTrue ( Sim::userInRole('LECTURER,PROFESSOR,*') );
		// clean-up
		unset($_SESSION['sim_user']);
	}


	function test__authController__beforeAction(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'index';
		// auto-init when no user record
		R::wipe('user');
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = true;
			$this->assertPattern('/FUSEBOX-REDIRECT/', $e->getMessage());
			$this->assertPattern('/'.preg_quote(F::url('auth.init'), '/').'/i', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		// do not auto-init when has user record
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		$this->assertTrue($id);
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = true;
			throw $e;
		}
		$this->assertFalse($hasRedirect);
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__index(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'index';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		// redirect to default page when logged in
		Auth::login(array('username' => 'foo'), true);
		try {
			$hasError = $hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
			$hasError = preg_match('/PHP ERROR/i', $output);
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
			$hasError = !$hasRedirect;
			$this->assertPattern('/'.preg_quote(F::config('defaultCommand'), '/').'/i', $e->getMessage());
		}
		$this->assertFalse($hasError);
		$this->assertTrue($hasRedirect);
		Auth::logout();
		// successfully show login form
		// ===> should have {submit} and {forgot password} links
		// ===> should not be any error
		try {
			$hasError = $hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
			$hasError = preg_match('/PHP ERROR/i', $output);
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
			$hasError = !$hasRedirect;
			$this->assertPattern('/'.preg_quote(F::url(F::config('defaultCommand')), '/').'/i', $e->getMessage());
		}
		$this->assertFalse($hasError);
		$this->assertFalse($hasRedirect);
		$this->assertPattern('/'.preg_quote(F::url('auth.login'), '/').'/i', $output);
		$this->assertPattern('/'.preg_quote(F::url('auth.forgot'), '/').'/i', $output);
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__forgot(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'forgot';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		// successfully show forgot password form
		// ===> should have {return to login} and {submit} links
		// ===> should not be any error
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
			$hasError = preg_match('/PHP ERROR/i', $output);
		} catch (Exception $e) {
			$hasError = preg_match('/FUSEBOX-ERROR/', $e->getMessage());
		}
		$this->assertFalse($hasError);
		$this->assertPattern('/'.preg_quote(F::url('auth.reset-password'), '/').'/i', $output);
		$this->assertPattern('/'.preg_quote(F::url('auth.index'), '/').'/i', $output);
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__resetPassword(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'reset-password';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar', 'email' => 'foo@bar.com'));
		$id = R::store($bean);
		// missing email
		$arguments['data'] = array();
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = preg_match('/FUSEBOX-ERROR/', $e->getMessage());
			$this->assertPattern('/no email was provided/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		// successfully reset password
		// ===> email sent and redirect to forgot password page
		$arguments['data']['email'] = 'foo@bar.com';
		try {
			$hasError = $hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
			$hasError = preg_match('/PHP ERROR/i', $output);
		} catch (Exception $e) {
			$hasError = preg_match('/FUSEBOX-ERROR/', $e->getMessage());
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
			$this->assertPattern('/'.preg_quote(F::url('auth.forgot'), '/').'/i', $e->getMessage());
		}
		$this->assertFalse($hasError);
		$this->assertTrue($hasRedirect);
		$bean = R::load('user', $id);
		$this->assertTrue( $bean->password != 'bar' );
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__login(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'login';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foobar', 'password' => '123456', 'email' => 'foo@bar.com'));
		$id = R::store($bean);
		// no data was submitted
		$arguments['data'] = array();
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = preg_match('/FUSEBOX-ERROR/', $e->getMessage());
			$this->assertPattern('/no data were submitted/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		$this->assertFalse( Auth::user() );
		// successful login by username
		$arguments['data'] = array('username' => 'foobar', 'password' => '123456');
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertTrue( Auth::user('username') == 'foobar' );
		$this->assertFalse( !empty($_SESSION['flash']) and $_SESSION['flash']['type'] == 'danger' );
		if ( isset($_SESSION['flash']) ) unset($_SESSION['flash']);
		Auth::logout();
		// successful login by email (user types email into username field)
		$arguments['data'] = array('username' => 'foo@bar.com', 'password' => '123456');
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertTrue( Auth::user('username') == 'foobar' );
		$this->assertFalse( !empty($_SESSION['flash']) and $_SESSION['flash']['type'] == 'danger' );
		if ( isset($_SESSION['flash']) ) unset($_SESSION['flash']);
		Auth::logout();
		// login failure with wrong password
		$arguments['data'] = array('username' => 'foobar', 'password' => '999999');
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertFalse( Auth::user() );
		$this->assertTrue( !empty($_SESSION['flash']) and $_SESSION['flash']['type'] == 'danger' );
		if ( isset($_SESSION['flash']) ) unset($_SESSION['flash']);
		Auth::logout();
		// login failure with wrong username
		$arguments['data'] = array('username' => 'ABCxyz', 'password' => '123456');
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertFalse( Auth::user() );
		$this->assertTrue( !empty($_SESSION['flash']) and $_SESSION['flash']['type'] == 'danger' );
		if ( isset($_SESSION['flash']) ) unset($_SESSION['flash']);
		Auth::logout();
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__logout(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'logout';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		// everyone can logout
		// ===> should not be any error
		// ===> then redirect to index
		$_SESSION['auth_user'] = array('role' => 'GUEST', 'username' => 'foobar');
		$this->assertTrue( Auth::user() );
		try {
			$hasError = $hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
			$hasError = !$hasRedirect;
		}
		$this->assertFalse($hasError);
		$this->assertTrue($hasRedirect);
		$this->assertFalse( Auth::user() );
		$this->assertFalse( Sim::user() );
		// even no-login can also logout
		// ===> should not be any error
		unset($_SESSION['auth_user']);
		$this->assertFalse( Auth::user() );
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = !preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertFalse($hasError);
		$this->assertFalse( Auth::user() );
		$this->assertFalse( Sim::user() );
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


	function test__authController__startSim(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'start-sim';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		// only super or admin is allowed
		$_SESSION['auth_user'] = array('role' => 'USER');
		$this->assertTrue( Auth::user() );
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = true;
			$this->assertPattern('/disallowed/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		$this->assertFalse( Sim::user() );
		// no user was specified
		$_SESSION['auth_user'] = array('role' => 'ADMIN');
		$this->assertTrue( Auth::user() );
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = true;
			$this->assertPattern('/no user was specified/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		$this->assertFalse( Sim::user() );
		// sucessful user-sim
		$arguments['user_id'] = $id;
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertTrue( Sim::user() );
		// clean-up
		unset($fusebox, $_SESSION['auth_user']);
		Sim::end();
		R::nuke();
	}


	function test__authController__endSim(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'end-sim';
		// create dummy user (to avoid auto-init)
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		// only super or admin is allowed
		$_SESSION['auth_user'] = array('role' => 'USER');
		$this->assertTrue( Auth::user() );
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = true;
			$this->assertPattern('/disallowed/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		// pretend to be super-user
		// ===> user-sim session should be cleared
		// ===> then return to index
		$_SESSION['auth_user'] = array('role' => 'SUPER');
		$_SESSION['sim_user'] = array('role' => 'GUEST');
		$this->assertTrue( Sim::user() );
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
			$this->assertPattern('/'.preg_quote(F::url('auth'), '/').'/i', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertFalse( Sim::user() );
		// check admin-user as well
		$_SESSION['auth_user'] = array('role' => 'ADMIN');
		$_SESSION['sim_user'] = array('role' => 'GUEST');
		$this->assertTrue( Sim::user() );
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = preg_match('/FUSEBOX-REDIRECT/', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$this->assertFalse( Sim::user() );
		// clean-up
		$fusebox = null;
		unset($fusebox, $_SESSION['auth_user']);
		if (isset($_SESSION['sim_user']) ) unset($_SESSION['sim_user']);
		R::nuke();
	}


	function test__authController__init(){
		global $fusebox;
		Framework::createAPIObject();
		Framework::loadDefaultConfig();
		$fusebox->config['appPath'] = dirname(dirname(__FILE__)).'/app/';
		Framework::setControllerAction();
		Framework::setMyself();
		// define action to run
		$fusebox->controller = 'auth';
		$fusebox->action = 'init';
		// should stop when already has user
		$bean = R::dispense('user');
		$bean->import(array('username' => 'foo', 'password' => 'bar'));
		$id = R::store($bean);
		try {
			$hasError = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasError = true;
			$this->assertPattern('/user account already exists/i', $e->getMessage());
		}
		$this->assertTrue($hasError);
		R::wipe('user');
		// super-user should be created (and return to index)
		try {
			$hasRedirect = false;
			ob_start();
			include dirname(dirname(__FILE__)).'/app/controller/auth_controller.php';
			$output = ob_get_clean();
		} catch (Exception $e) {
			$hasRedirect = true;
			$this->assertPattern('/FUSEBOX-REDIRECT/', $e->getMessage());
			$this->assertPattern('/'.preg_quote(F::url('auth'), '/').'/i', $e->getMessage());
		}
		$this->assertTrue($hasRedirect);
		$bean = R::findOne('user', 'role = ?', array('SUPER'));
		$this->assertTrue($bean->id);
		// clean-up
		$fusebox = null;
		unset($fusebox);
		R::nuke();
	}


}