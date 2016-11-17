<?php /*
<fusedoc>
	<description>
		Helper component for Fuseboxy framework
	</description>
	<properties name="version" value="1.0" />
	<io>
		<in>
			<boolean name="FUSEBOX_UNIT_TEST" scope="$GLOBALS" optional="yes" />
		</in>
	</io>
</fusedoc>
*/
class F {


	// check whether this is (jQuery) ajax request
	public static function ajaxRequest() {
		return ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' );
	}


	// controller + action
	public static function command($key='') {
		global $fusebox;
		if ( $key == null ) {
			return $fusebox->controller.$fusebox->config['commandDelimiter'].$fusebox->action;
		} elseif ( strtolower($key) == 'controller' ) {
			return $fusebox->controller;
		} elseif ( strtolower($key) == 'action' ) {
			return $fusebox->action;
		} else {
			return false;
		}
	}


	// get config
	public static function config($key=null) {
		global $fusebox;
		if ( empty($key) ) {
			return $fusebox->config;
		} elseif ( isset($fusebox->config[$key]) ) {
			return $fusebox->config[$key];
		} else {
			return false;
		}
	}


	// show error, send header, and abort operation
	public static function error($msg='error', $condition=true) {
		global $fusebox;
		if ( $condition ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 403 Forbidden");
			$fusebox->error = $msg;
			if ( isset($fusebox->config['errorController']) ) {
				include $fusebox->config['errorController'];
				die();
			} else {
				throw new Exception("[FUSEBOX-ERROR] ".self::command()." - ".$fusebox->error);
			}
		}
	}


	// case-sensitive check on command (with wildcard), for example...
	// - specific controller + action ===> F::is('site.index')
	// - specific controller ===> F::is('site.*')
	// - specific action ===> F::is('*.index')
	public static function is($commandPatternList) {
		global $fusebox;
		// allow checking multiple command-patterns
		if ( !is_array($commandPatternList) ) {
			$commandPatternList = explode(',', $commandPatternList);
		}
		// check each user-provided command-pattern
		foreach ( $commandPatternList as $commandPattern ) {
			$commandPattern = self::parseCommand($commandPattern);
			// consider match when either one is ok
			if ( in_array($commandPattern['controller'], array('*', $fusebox->controller)) and in_array($commandPattern['action'], array('*', $fusebox->action)) ) {
				return true;
			}
		}
		// no match
		return false;
	}


	// invoke specific command
	// ===> allow accessing arguments scope
	public static function invoke($command, $arguments=array()) {
		global $fusebox;
		// create stack container to keep track current command
		if ( !isset($fusebox->invokeQueue) ) $fusebox->invokeQueue = array();
		// current controller & action
		$fusebox->invokeQueue[] = $command;
		$arr = self::parseCommand($command);
		$fusebox->controller = $arr['controller'];
		$fusebox->action = $arr['action'];
		// determine the controller to load
		$controllerPath = "{$fusebox->config['appPath']}/controller/{$fusebox->controller}_controller.php";
		// check controller existence
		F::pageNotFound( !file_exists($controllerPath) );
		// load controller
		include $controllerPath;
		// trim stack after run
		if ( !empty($fusebox->invokeQueue) ) {
			$nextCommand = array_pop($fusebox->invokeQueue);
			$arr = self::parseCommand($nextCommand);
			$fusebox->controller = $arr['controller'];
			$fusebox->action = $arr['action'];
		}
		// done!
		return true;
	}


	// check wether this is invoked from command-line-interface
	public static function isCLI() {
		return ( php_sapi_name() === 'cli' );
	}


	// check whether this is an internal invoke
	// ===> first request, which is not internal, was invoked by framework core (fuseboxy.php)
	public static function isInvoke() {
		global $fusebox;
		return !empty($fusebox->invokeQueue);
	}


	// show 404 not found page
	public static function pageNotFound($condition=true) {
		global $fusebox;
		if ( $condition ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 404 Not Found");
			$fusebox->error = 'Page not found';
			if ( isset($fusebox->config['errorController']) ) {
				include $fusebox->config['errorController'];
				die();
			} else {
				throw new Exception("[FUSEBOX-PAGE-NOT-FOUND] ".self::command()." - ".$fusebox->error);
			}
		}
	}


	// extract controller & action from command
	public static function parseCommand($command) {
		global $fusebox;
		$arr = explode($fusebox->config['commandDelimiter'], $command, 2);
		return array(
			'controller' => $arr[0],
			'action' => !empty($arr[1]) ? $arr[1] : 'index'
		);
	}


	// redirect to specific command
	// ===> command might include query-string
	public static function redirect($url, $condition=true, $delay=0) {
		// check internal or external link
		$isExternalUrl = ( substr(strtolower(trim($url)), 0, 7) == 'http://' or substr(strtolower(trim($url)), 0, 8) == 'https://' );
		if ( !$isExternalUrl ) $url = self::url($url);
		// check if any delay (in second)
		$headerString = empty($delay) ? "Location: {$url}" : "Refresh: {$delay}; url={$url}";
		// do not redirect if condition false
		if ( !$condition ) $headerString = false;
		// simply return header-string when unit-test
		if ( !empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) {
			return $headerString;
		// perform redirect (when necessary)
		} elseif ( !empty($headerString) ) {
			header($headerString);
			die();
		}
	}


	// transform url (with param)
	// ===> append fusebox-myself to url
	// ===> turn it into beautify-url (if enable)
	public static function url($commandWithQueryString='') {
		global $fusebox;
		// no command defined
		// ===> simply return self (no matter url-rewrite or not)
		if ( empty($commandWithQueryString) ) {
			return $fusebox->self;
		// no rewrite with query-string
		// ===> prepend self and command-variable
		} elseif ( empty($fusebox->config['urlRewrite']) ) {
			return $fusebox->myself.$commandWithQueryString;
		}
		// rewrite (with or without query-string)
		// ===> transform to beauty-url
		// ===> check route as well
		$qs = explode('&', $commandWithQueryString);
		// first element has command-delimiter and no equal-sign
		// ===> first element is command
		// ===> replace first occurrence of delimiter with slash (if any)
		if ( strpos($qs[0], '=') === false ) {
			$qs[0] = explode($fusebox->config['commandDelimiter'], $qs[0], 2);
			$qs[0] = implode('/', $qs[0]);
		}
		// turn query-string into path-like-query-string
		$qsPath = implode('/', $qs);
		$qsPath = preg_replace('~^/+|/+$|/(?=/)~', '', $qsPath);  // remove multi-slash
		$qsPath = trim($qsPath, '/');  // trim leading and trailing slash
		// compare it against each route pattern
		/*if ( !empty($fusebox->config['route']) ) {
			foreach ( $fusebox->config['route'] as $urlPattern => $qsReplacement ) {
				( exploring solution to turn matched command to routed-url )
			}
		}*/
		// if no route defined or no match
		// ===> simply prepend self to query-string-path
		return $fusebox->self.$qsPath;
	}


}