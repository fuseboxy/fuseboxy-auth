<?php /*
<fusedoc>
	<description>
		Core component of Fuseboxy framework
	</description>
	<properties name="version" value="1.0" />
	<io>
		<in>
			<string  name="FUSEBOX_CONFIG_PATH" scope="$GLOBALS" optional="yes" default="../../../config/fusebox_config.php" />
			<string  name="FUSEBOX_HELPER_PATH" scope="$GLOBALS" optional="yes" default="./F.php" />
			<boolean name="FUSEBOX_UNIT_TEST"   scope="$GLOBALS" optional="yes" />
		</in>
	</io>
</fusedoc>
*/
class Framework {


	// initiate fusebox-api variable
	public static function createAPIObject() {
		global $fusebox;
		$fusebox = new StdClass();
	}


	// define config default value
	public static function loadDefaultConfig() {
		global $fusebox;
		$fusebox->config = array(
			'defaultCommand' => 'site',
			'commandVariable' => 'fuseaction',
			'commandDelimiter' => '.',
			'appPath' => str_replace('\\', '/', dirname(dirname(__FILE__))).'/',
		);
	}


	// load user-defined config
	public static function loadCustomConfig() {
		global $fusebox;
		$configPath = isset($GLOBALS['FUSEBOX_CONFIG_PATH']) ? $GLOBALS['FUSEBOX_CONFIG_PATH'] : dirname(dirname(dirname(__FILE__))).'/config/fusebox_config.php';
		if ( file_exists($configPath) ) {
			$fusebox->config = include $configPath;
		} else {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-CONFIG-NOT-FOUND] Config file not found ({$configPath})");
		}
		if ( !is_array($fusebox->config) ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-CONFIG-NOT-DEFINED] Config file must return an Array");
		}
	}


	// load framework utility component
	// ===> when {$fusebox} api is ready
	public static function loadHelper() {
		global $fusebox;
		$helperPath = isset($GLOBALS['FUSEBOX_HELPER_PATH']) ? $GLOBALS['FUSEBOX_HELPER_PATH'] : dirname(__FILE__).'/F.php';
		if ( file_exists($helperPath) ) {
			include $helperPath;
		} else {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-HELPER-NOT-FOUND] Helper class file not found ({$helperPath})");
		}
		if ( !class_exists('F') ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-HELPER-NOT-DEFINED] Helper class (F) not defined");
		}
	}


	// validate config
	public static function validateConfig() {
		global $fusebox;
		// check required config
		foreach ( array('defaultCommand','commandVariable','commandDelimiter','appPath') as $key ) {
			if ( empty($fusebox->config[$key]) ) {
				if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
				throw new Exception("[FUSEBOX-MISSING-CONFIG] Fusebox config variable {{$key}} is required");
			}
		}
		// check command-variable
		if ( in_array(strtolower($fusebox->config['commandVariable']), array('controller','action')) ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-INVALID-CONFIG] Config {commandVariable} can not be 'controller' or 'action'");

		}
		// check command-delimiter
		if ( !in_array($fusebox->config['commandDelimiter'], array('.', '-', '_')) ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception('[FUSEBOX-INVALID-CONFIG] Config {commandDelimiter} can only be dot (.), dash (-), or underscore (_)');
		}
		// check app-path
		if ( !is_dir($fusebox->config['appPath']) ) {
			if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("[FUSEBOX-INVALID-CONFIG] Directory specified in config {appPath} does not exists ({$fusebox->config['appPath']})");
		}
	}


	// api variables
	public static function setMyself() {
		global $fusebox;
		if ( !empty($fusebox->config['urlRewrite']) ) {
			$fusebox->self = dirname($_SERVER['SCRIPT_NAME']).'/';
			$fusebox->myself = $fusebox->self;
		} else {
			$fusebox->self = $_SERVER['SCRIPT_NAME'];
			$fusebox->myself = "{$fusebox->self}?{$fusebox->config['commandVariable']}=";
		}
	}


	// auto-load files or directories (non-recursive)
	public static function autoLoad() {
		global $fusebox;
		if ( !empty($fusebox->config['autoLoad']) ) {
			foreach ( $fusebox->config['autoLoad'] as $originalPath ) {
				// check type
				$isWildcard = ( strpos($originalPath, '*') !== false );
				$isExistingDir = ( file_exists($originalPath) and is_dir($originalPath) );
				$isExistingFile = ( file_exists($originalPath) and is_file($originalPath) );
				// adjust argument
				$path = $originalPath;
				if ( $isExistingDir ) $path .= "/*";
				$path = str_replace("\\", "/", $path);
				$path = str_replace("//", "/", $path);
				// throw error when auto-load path not found
				if ( !$isWildcard and !$isExistingDir and !$isExistingFile ) {
					if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
					throw new Exception("[FUSEBOX-INVALID-CONFIG] Auto-load path not found ({$path})");
				}
				// include all file specified
				foreach ( glob($path) as $file ) {
					if ( is_file($file) ) require_once $file;
				}
			}
		}
	}


	// extract command and url variables from beauty-url
	// ===> work closely with {$fusebox->config['route']} and F::url()
	public static function urlRewrite() {
		global $fusebox;
		// server variable {PATH_INFO} will be available when {RewriteEngine On} in <.htaccess>
		if ( !empty($fusebox->config['urlRewrite']) and !empty($_SERVER['PATH_INFO']) ) {
			// cleanse the route config (and keep the sequence)
			if ( isset($fusebox->config['route']) ) {
				$fixedRoute = array();
				foreach ( $fusebox->config['route'] as $urlPattern => $qsReplacement ) {
					// clean unnecessary spaces
					$urlPattern = trim($urlPattern);
					$qsReplacement = trim($qsReplacement);
					// prepend forward-slash (when necessary)
					if ( substr($urlPattern, 0, 1) !== '/' and substr($urlPattern, 0, 2) != '\\/' ) {
						$urlPattern = '/'.$urlPattern;
					}
					// remove multi-(forward-)slash
					do { $urlPattern = str_replace('//', '/', $urlPattern); } while ( strpos($urlPattern, '//') !== false );
					// escape forward-slash
					$urlPattern = str_replace('/', '\\/', $urlPattern);
					// fix double-escaped forward-slash
					$urlPattern = str_replace('\\\\/', '\\/', $urlPattern);
					// put into container
					$fixedRoute[$urlPattern] = $qsReplacement;
				}
				$fusebox->config['route'] = $fixedRoute;
			}
			// cleanse the path-like-query-string
			$qsPath = $_SERVER['PATH_INFO'];
			$qsPath = str_replace('\\', '/', $qsPath);  // unify to forward-slash
			do { $qsPath = str_replace('//', '/', $qsPath); } while ( strpos($qsPath, '//') !== false );  // remove multi-(forward-)slash
			// check if there is route match...
			// ===> apply query-string-replacement of the first match
			$hasRouteMatch = false;
			if ( isset($fusebox->config['route']) ) {
				foreach ( $fusebox->config['route'] as $urlPattern => $qsReplacement ) {
					// if path-like-query-string match the route pattern...
					if ( !$hasRouteMatch and preg_match("/{$urlPattern}/", $qsPath) ) {
						// turn it into a query-string
						$qs = preg_replace("/{$urlPattern}/", $qsReplacement, $qsPath);
						// mark flag
						$hasRouteMatch = true;
					}
				}
			}
			// if path-like-query-string match none of the route...
			// ===> turn path-info into query-string
			if ( !$hasRouteMatch ) {
				$arr = explode('/', trim($qsPath, '/'));
				if ( count($arr) == 1 and $arr[0] == '' ) $arr = array();
				$qs = '';
				// turn path-like-query-string into query-string
				// ===> extract (at most) first two elements for command-variable
				// ===> treat as command-variable when element was unnamed (no equal-sign)
				// ===> treat as url-param when element was named (has equal-sign)
				if ( count($arr) and strpos($arr[0], '=') === false ) {  // 1st time
					$qs .= ( $fusebox->config['commandVariable'] . '=' . array_shift($arr) );
				}
				if ( count($arr) and strpos($arr[0], '=') === false ) {  // 2nd time
					$qs .= ( $fusebox->config['commandDelimiter'] . array_shift($arr) );
				}
				// join remaining elements into query-string
				$qs .= ( '&' . implode('&', $arr) );
			}
			// merge original query-string (if any)
			if ( !empty($_SERVER['QUERY_STRING']) ) {
				$qs .= ( '&' . $_SERVER['QUERY_STRING'] );;
			}
			// trim leading and-sign from query-string (if any)
			$qs = trim($qs, '&');
			// put parameters of query-string into {$_GET} scope
			$qsArray = explode('&', $qs);
			foreach ( $qsArray as $param ) {
				$param = explode('=', $param, 2);
				$paramKey = isset($param[0]) ? $param[0] : '';
				$paramVal = isset($param[1]) ? $param[1] : '';
				if ( !empty($paramKey) ) {
					// simple parameter
					if ( strpos($paramKey, '[') === false ) {
						$_GET[$paramKey] = $paramVal;
					// array parameter
					} else {
						$arrayDepth = substr_count($paramKey, '[');
						$arrayKeys = explode('[', str_replace(']', '', $paramKey));
						foreach ( $arrayKeys as $i => $singleArrayKey ) {
							if ( $i == 0 ) $pointer = &$_GET;
							if ( $singleArrayKey != '' ) {
								$pointer[$singleArrayKey] = isset($pointer[$singleArrayKey]) ? $pointer[$singleArrayKey] : array();
								$pointer = &$pointer[$singleArrayKey];
							} else {
								$pointer[count($pointer)] = isset($pointer[count($pointer)]) ? $pointer[count($pointer)] : array();
								$pointer = &$pointer[count($pointer)-1];
							}
							if ( $i+1 == count($arrayKeys) ) $pointer = $paramVal;
						}
						unset($pointer);
					}
				}
			}
			// also update request scope
			$_REQUEST += $_GET;
			// also update query-string in server-scope
			$_SERVER['QUERY_STRING'] = $qs;
		} // if-url-rewrite
	}


	// formUrl2arguments
	// ===> default merging POST & GET scope
	// ===> user could define array of scopes to merge
	public static function formUrl2arguments() {
		global $fusebox;
		global $arguments;
		if ( isset($fusebox->config['formUrl2arguments']) and !empty($fusebox->config['formUrl2arguments']) ) {
			global $arguments;
			// config default
			if ( $fusebox->config['formUrl2arguments'] === true or $fusebox->config['formUrl2arguments'] === 1 ) {
				$fusebox->config['formUrl2arguments'] = array($_GET, $_POST);
			}
			// copy variables from scope to container (precedence = first-come-first-serve)
			if ( is_array($fusebox->config['formUrl2arguments']) ) {
				$arguments = array();
				foreach ( $fusebox->config['formUrl2arguments'] as $scope ) $arguments += $scope;
			// validation
			} else {
				if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) header("HTTP/1.0 500 Internal Server Error");
				throw new Exception("[FUSEBOX-INVALID-CONFIG] Config {formUrl2arguments} must be Boolean or Array");
			}
		}
	}


	// get controller & action out of command
	public static function setControllerAction() {
		global $fusebox;
		// if no command was defined, use {defaultCommand} in config
		if ( F::isCLI() ) {
			$command = !empty($argv[1]) ? $argv[1] : $fusebox->config['defaultCommand'];
		} elseif ( !empty($_GET[$fusebox->config['commandVariable']]) ) {
			$command = $_GET[$fusebox->config['commandVariable']];
		} elseif ( !empty($_POST[$fusebox->config['commandVariable']]) ) {
			$command = $_POST[$fusebox->config['commandVariable']];
		} else {
			$command = $fusebox->config['defaultCommand'];
		}
		// parse controller & action
		$parsed = F::parseCommand($command);
		// modify fusebox-api variable
		$fusebox->controller = $parsed['controller'];
		$fusebox->action = $parsed['action'];
	}


	// run specific controller and action
	public static function run() {
		global $fusebox;
		global $arguments;
		// main process...
		self::createAPIObject();
		self::loadDefaultConfig();
		self::loadCustomConfig();
		self::validateConfig();
		self::loadHelper();
		self::setMyself();
		self::autoLoad();
		self::urlRewrite();
		self::formUrl2arguments();
		self::setControllerAction();
		// load controller and... RUN!!!
		$__controllerPath = "{$fusebox->config['appPath']}/controller/{$fusebox->controller}_controller.php";
		F::pageNotFound( !file_exists($__controllerPath) );
		include $__controllerPath;
	}


} // class-Framework


// do not auto-run when unit-test
if ( empty($GLOBALS['FUSEBOX_UNIT_TEST']) ) {
	Framework::run();
}