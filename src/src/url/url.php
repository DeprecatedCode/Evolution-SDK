<?php

/**
 * Auto URL Routing
 *
 * @package evolution
 * @author David Boskovic
 */
class e_url {

	/**
	 * the requested url, eg: /something/else/now
	 *
	 * @var string
	 */
	public $path = '';
	public $id = false; // if an identifier has been passed in the url structure, this will be populated.
	public $view_path = false; // if an identifier has been passed in the url structure, this will be populated.
	
	/**
	 * the domain, broken up into an array, eg: array(1 => 'com', 2 => "domain", 3 => 'www')
	 *
	 * @var array
	 */
	public $domain = array();
	
	/**
	 * The domain. String format
	 *
	 * @var string
	 */
	public $server = '';
	public $portal = false;

	/**
	 * an array of segments, eg: /something/else/now = array(0 => 'something', 1 => 'else', 2 => 'now')
	 *
	 * @var string
	 */
	public $segments = array();
	public $http_root = array();

	/**
	 * Loaded Controllers
	 *
	 * @var string
	 */
	private static $controllers = array();
	
	/**
	 * Track whether or not a specific url segment has been matched yet.
	 *
	 * @var array
	 */
	private static $matched = array();
	
	/**
	 * Referrer URL
	 *
	 * @var string
	 */
	public $referer;

	/**
	 * Current Pointer
	 *
	 * @var integer
	 */
	public $pointer = 1;
	
	/**
	 * Last segment
	 */
	public function last() {
		$c = count($this->segments);
		return $this->segments[$c - 1];
	}
	
	/**
	 * Analyze the url and see if we can find a controller or interface to run.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function init() {
		//echo '<div style="font-family:courier;font-size:11px;white-space:pre">';
		
		$this->initialize();
		
		# load the current url configuration
		$urls = e::$configure->routing + e::$configure->routing_local;
	
		$matched = false;
		foreach($urls as $url) {
			
			if(isset($url['deprecated']) AND $url['deprecated'] == 1) {
				$type = 
					isset($url['controller']) ? 'controller' : 
					(isset($url['interface']) ? 'interface' : 
					(isset($url['interface_group']) ? 'interface_group' :  
					(isset($url['custom']) ? 'custom' : 
					(isset($url['portal']) ? 'portal' :
					(isset($url['actions']) ? 'actions' : false
				)))));
				if(url::assign($url['matches'], $type, $url[$type])) { $matched = true; break;}
			}
			elseif(!$matched && $this->_match($url['matches'], 1)) {
				$method = '_map_'.$url['target']['type'];
				if(method_exists($this, $method))
					$matched = $this->$method($url);
			}
		}
		if(!$matched) {
			header("Status: 404 Not Found");
			echo "404 Error - Page Not Found";
			//e::$error->fault(100,'page_not_found');
		}
	}
	
	public function _match($compare, $segment) {
		$segment = $this->segment(1);
		if($compare == $segment) return true;
		else return false;
	}
	
	public function _map_portal($config, $autoload = true) {
		$this->portal = $config['target']['name'];
		$matched = false;
		if($autoload) {
			$matched = $this->_autoload_action($config);
			if(!$matched && (!isset($config['config']['controller']) || $config['config']['controller'] == 'auto')) $matched = $this->_autoload_controller();
			if(!$matched && (!isset($config['config']['interface']) || $config['config']['interface'] == 'auto')) $matched = $this->_autoload_interface();
			if(!$matched && (!isset($config['config']['page']) || $config['config']['page'] == 'auto')) $matched = $this->_autoload_page();
		}
		return $matched;
	}
	
	public function _map_interface($config) {
		$dir = ROOT_PORTALS;
		
		$interface = $config['target']['name'];
		
		if(file_exists($dir.'/'.$interface.'.ixml')) {	
			$parse = new Interface_Parser($dir.'/'.$interface.'.ixml');
			echo (string) $parse->object;
			return true;
		}
		return false;
	}
	public function _autoload_action($config) {
		
		if(!$this->portal) return false;
		++$this->pointer;
		$action_slug = isset($config['config']['action_slug']) ? $config['config']['action_slug'] : 'do';
		if($this->segment() != $action_slug) {
			--$this->pointer;
			return false;
		}
		++$this->pointer;
		
		
		$segment = $this->segment($this->pointer);
		$class = 'Action_'.$segment;
		++$this->pointer;
		$segment = $this->segment($this->pointer);
		if(class_exists($class, true)) {
			$att = new $class;
			return true;
		}
		$this->pointer = $this->pointer - 3;
		return false;
		
	}
	
	public function _autoload_controller() {
		if(!$this->portal) return false;
		++$this->pointer;
		
		$segment = $this->segment($this->pointer);
		$class = 'Controller_'.$segment;

		++$this->pointer;
		$segment = $this->segment($this->pointer);
		if(class_exists($class, true)) {
			$parameters = array_slice($this->segments, $this->pointer);
			$methods = get_class_methods($class);

			if(!is_array($methods))die("The class $class doesn't exist.");
			$methods = array_flip($methods);

			$segment = $segment && isset($methods[$segment]) ? $segment : '_default';
			if($segment == '_default' && $this->segment()) {
				$parameters = array_slice($this->segments, $this->pointer-1);				
			}
			$segment = str_replace('-', '_', $segment);
			if(isset($methods[$segment])) {
				$att = new $class;
				call_user_func_array(array($att,$segment), $parameters);
				return true;
			}
		}
		--$this->pointer;
		--$this->pointer;
		return false;
		
	}
	
	public function _autoload_page($dir = false) {
		if(!$this->portal) return false;
		if(!$dir) {
			$dir = ROOT_PORTALS."/$this->portal/pages";
		}
		$interface = $this->segment();
		
		if($interface && is_dir($dir.'/'.$interface)) {
			++$this->pointer;
			$matched = $this->_autoload_page($dir.'/'.$interface);
			--$this->pointer;
			return $matched;
		}
		elseif(file_exists($dir.'/'.$interface.'.page')) {	
			++$this->pointer;
			$page = new e_Page_Builder($dir.'/'.$interface.'.page');
			$page->publish();
			return true;
		}
		elseif(file_exists($dir.'/index.page') && !$interface) {
			++$this->pointer;	
			$page = new e_Page_Builder($dir.'/index.page');
			$page->publish();
			return true;
		}
		return false;
	}
	private function _autoload_interface($dir = false) {
		if(!$this->portal) return false;
		if(!$dir) {
			$dir = ROOT_PORTALS."/$this->portal/interface";
			++$this->pointer;
		}
		
		$interface = $this->segment($this->pointer);
		
		if($interface && is_dir($dir.'/'.$interface)) {
			++$this->pointer;
			$matched = $this->_autoload_interface($dir.'/'.$interface);
			--$this->pointer;
			return $matched;
		}
		elseif($interface == 'view' && is_dir($dir.'/_view')) {
			++$this->pointer;
			$matched = $this->_autoload_interface($dir.'/_view');
			--$this->pointer;
			return $matched;
		}
		elseif(file_exists($dir.'/'.$interface.'.ixml')) {	
			$parse = new Interface_Parser($dir.'/'.$interface.'.ixml');
			echo (string) $parse->object;
			return true;
		}
		elseif(file_exists($dir.'/index.ixml') && !$interface) {	
			$parse = new Interface_Parser($dir.'/index.ixml');
			echo (string) $parse->object;
			return true;
		}
		elseif(file_exists($dir.'/'.$this->segment(-1).'.ixml') && !$interface) {	
			$parse = new Interface_Parser($dir.'/'.$this->segment(-1).'.ixml');
			echo (string) $parse->object;
			return true;
		} else {
			if(is_numeric($this->segment()) || $this->segment(-1) == 'view') {
				$this->id = $this->segment();
				++$this->pointer;
				$this->view_path = $this->trace();
			}
			if(!$this->segment()) {
				if(file_exists($dir.'/view.ixml')) {	
					$parse = new Interface_Parser($dir.'/view.ixml');
					echo (string) $parse->object;
					return true;
				}
			} else {
				$interface = $this->segment();
				if(file_exists($dir.'/'.$interface.'.ixml')) {	
					$parse = new Interface_Parser($dir.'/'.$interface.'.ixml');
					echo (string) $parse->object;
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * Initialize the url handler.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function initialize() {

		# initialize variables
		$this->path = $_SERVER['REQUEST_URI'];
		$this->referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->domain = $this->_parse_domain();
		$this->http_root = @e::$env['http_path'] ? $this->_parse_path(e::$env['http_path']) : false;
		$this->segments = $this->_parse_path();
		
		# define SUBDOMAIN
		$subDomain = !empty($this->domain[3]) ? $this->domain[3] : '';
		define('SUBDOMAIN', $subDomain);
	}
	
	/**
	 * Assign a specific interface or controller to a url segment.
	 *
	 * @param string $segment 
	 * @param string $type 
	 * @param string $value 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function assign($segment, $type, $value) {

		# pointer to reassign at end of execution
		$pointer = $this->pointer;
		
		if(strpos($segment, '/')) {
			$segs = explode('/', $segment);

			foreach($segs as $key => $segment) {

				if($this->_match_handle($segment, $this->segment($key+1)))
					$this->pointer += 1;
				else {
					$this->pointer = $pointer;
					return false;
				}
			}
			
			$this->pointer -= 1;
		}
		if(!isset($this->matched[$this->pointer]) && $this->_match_handle($segment, $this->segment())) {
			switch($type) {
				case 'controller' :
					$file = ROOT_CONTROLLERS.'/'.$value.'.php';
					if(file_exists($file)) {
		            	++$this->pointer;
						include $file;
						$this->auto($value);
		            	$this->pointer = $pointer;
		            	$this->matched[$pointer] = true;
						return true;
					}
					return false;
				break;
				case 'actions' :
					++$this->pointer;
            		$ac = $this->segment();
					$file = ROOT_ACTIONS.'/'.$ac.'.php';
					if(file_exists($file)) {
						include $file;
						$av = 'Action_'.$ac;
						$action = new $av;						
						return true;
					}
					return false;
				break;
				case 'custom' :
					$file = ROOT.'/'.$value;
					if(file_exists($file)) {
		            	++$this->pointer;
						include $file;
		            	$this->pointer = $pointer;
		            	$this->matched[$pointer] = true;
						return true;
					}
					return false;
				break;
				case 'interface' :			
					$parse = new Interface_Parser($value.'.ixml');
					echo (string)$parse->object;
					return true;
				break;
				case 'interface_group' :
					if($value == '@admin') $dir = ROOT_FRAMEWORK.'/manager/interface';
					//else $dir = ROOT_INTERFACE.'/'.$value;
					if(is_dir($dir)) {
						$this->pointer +=1;
						$page = urlencode($this->segment());
						if(!$page) $page = 'index';
						if(file_exists($dir.'/'.$page.'.ixml')) {
							$parse = new Interface_Parser($dir.'/'.$page.'.ixml');
							$t = microtime(true);
							echo (string)$parse->object;
							//var_dump(microtime(true)-$t);
							return true;
						}
					}
				break;
			}
		}
		$this->pointer = $pointer;
		return false;
	}
	
	public function auto($controller, $prefix = 'controller_') {
		#Only run if a controller hasn't already been run
		if(!isset($this->matched[$this->pointer])) {
			
			$segment = $this->segment();
			$parameters = array_slice($this->segments, $this->pointer);
			$class = $prefix.$controller;
			$methods = get_class_methods($class);
			
			if(!is_array($methods))die("The class $class doesn't exist.");
			$methods = array_flip($methods);
			$segment = $segment && isset($methods[$segment]) ? $segment : '_default';
			if($segment == '_default' && $this->segment()) {
				$parameters = array_slice($this->segments, $this->pointer-1);				
			}
			$segment = str_replace('-', '_', $segment);
			if(isset($methods[$segment])) {
				$att = new $class;
				call_user_func_array(array($att,$segment), $parameters);
			}
		}
	}
	
	public function Destroy() {
		$count = 1;
		while(count($this->controllers) >= $count) {
			unset($this->controllers[$count]);
			$count++;
		}
	}

	public function ReAssign($segment, $to)	{
		
		# redirect to new url
		if($this->_match_handle($segment, $this->segments[$this->pointer]))
			$this->redirect($this->Link($this->Trace().$to));

	}
	
	
	public function Position($no = null) {
		if(is_null($no)) return $this->pointer;
		else $this->pointer = $no;
	}
	
	
	public function Next() {
		++$this->pointer;
	}
	
	
	public function Prev() {
		--$this->pointer;
	}
	
	public function Trace($no = false) {
		$no = $no !== false ? $no : $this->pointer;
		
		if($no < 0) {
			$no = $no / -1;	// invert sign
			$no = $this->pointer - $no;
		}
		
		$path = '/';
		foreach($this->segments as $key => $val) {
			if($key >= $no) { break; } else {
				$path .= $val.'/';
			}
		}
		return $path;
	}

	
	public function segment($pointer = false) {
		
		# if no pointer has been specified, get the current pointer
		$pointer = $pointer ? $pointer : $this->pointer;
		
		# string "+integer"
		if( is_string($pointer) && substr($pointer, 0, 1) == '+') {
			$pointer = $this->pointer + str_replace('+', '', $pointer);
			return $this->segments[$pointer];			
		}
		
		# string
		elseif( is_string($pointer) ) {
			$labels = array_flip($this->labels);
			$pointer = $labels[$pointer];
				return $this->segments[$pointer];
		}
		
		# integer (negative)
		elseif( $pointer < 0 ) {
			$pointer = $this->pointer + $pointer;
			return $this->segments[$pointer];
			
		}
		
		# integer (positive)
		else return isset($this->segments[$pointer]) ? $this->segments[$pointer] : false;
	}
	
	public function Label($string, $condition = TRUE) {
		if($condition == FALSE) return FALSE;
		
		$labels = explode("/", $string);
		$output = array();
		if(substr($string, 0, 1) == '/') {
			unset($labels[0]);
			foreach($labels as $key => $val) {
				$this->labels[$key] = $val;
			}
		} else {
			foreach($labels as $key => $val) {
				$this->labels[$key + $this->pointer] = $val;
			}
		}
	}
	
	public function _load_object($segment, $pointer, array $parameters = array()) {
	
		# check if an object exists
		if(class_exists('Controller_'.$segment)) {
			$object = 'controller_'.$segment;
			$this->controllers[$pointer] = new $object;
			
			$methods = get_class_methods($this->controllers[$this->pointer - 1]);
			$methods = array_flip($methods);
	
			if(count($this->segments) == $pointer) {
				
				if(isset($methods['_default'])) {
					call_user_func_array(array($this->controllers[$this->pointer - 1], '_default'), $parameters);
				}
			}
		}
	}
	
	//Assigns all specially formatted GET variables ( /key:val/ ) to the $_GET superglobal
	public function _parse_get() {
		$pathComponents = explode('/', $this->path);
		foreach($pathComponents as $val){
			if(stripos($val, ':') !== FALSE){
				$getParts = explode(':', $val);
				$getKey = $getParts[0];
				$getVal = $getParts[1];
				
				//If query string is formatted like:  /url?key:val, then parse out the url
				if( stripos($getParts[0], '?') !== FALSE){
					$getKey = substr($getKey, stripos($getKey, '?')+1 );
				}
				
				//If query string is formatted like:  /url?key:val, then this key will be set: $_GET['key:val'].  Let's unset it.
				/* Commented out because there is potential for errors (ie, $GET keys getting deleted)
				if( isset($_GET[$getKey]) )
					unset($_GET[$getKey]);*/
				
				$_GET[$getKey] = $getVal;
			}
		}
	}

	public function _parse_path($uri = '') {
	
		if(empty($uri)) {
			$uri = $_SERVER['REQUEST_URI'];
			$ignore = $this->http_root;
		}
		else {
			$ignore = false;
		}
		
		$pathComponents = explode('?', $uri);
		if(empty($pathComponents[1])) $pathComponents[1] = '';
		
		list($path, $get) = $pathComponents;
		$path = explode("/", $path);

		$path = array_reverse($path);
		

		if($path[0] == '') {
			unset($path[0]);
		}
		$path = array_reverse($path);

		unset($path[0]);
		if($ignore) {
			$npath = array_diff_assoc($path, $ignore);
			array_unshift($npath, 'del');
			unset($npath[0]);
			$path = $npath;
		}
		
		return $path;
	}

	public function _parse_domain() {
		$this->server = $_SERVER['SERVER_NAME'];
		$array = explode('.', $_SERVER['SERVER_NAME']);
		$array = array_reverse($array);
		$pointer = 1;

		foreach($array as $domain) {
			$output[$pointer] = $domain;
			++$pointer;
		}

		// get the port if included in url
		$httpHost = explode(':',$_SERVER['HTTP_HOST']);
		if(empty($httpHost[1])) $httpHost[1] = ''; //Prevents port from being undefined on next line
		
		list($domain, $port) = $httpHost;
		$output['1'] = $port ? $output['1'].':'.$port : $output['1'];
		$this->server = $port ? $this->server.":".$port : $this->server;
		return $output;
	}

	public function _is_function_call($call) {

		return
			strstr($call, '::') ?
			trim(str_replace('::','',$call)) :
			false;

	}
	public function _match_handle($segmentr, $segment) {

		if(substr($segmentr, 0, 1) == '%') {
			if(match_type(substr($segmentr, 1), $segment)) {
				return true;
			}
		}
		elseif($segment == $segmentr) { #echo $segment.'=='.$segmentr; 
		return true; }

		return false;

	}



	public function Redirect($to)	{
		
		ob_end_clean();
		
		if(stripos($to, '://') === FALSE) {
			$url = $this->protocol(). '://' . $this->server . $this->Link($to);
		} else {
			$url = $to;
		}
		
		if (!headers_sent()){    // If headers not sent yet... then do php redirect
	        header('Location: '.$url);
	        echo "If this page does not redirect, <a href=\"$url\">click here</a> to continue";
	    } else {                 // If headers are sent... do javascript redirect... if javascript disabled, do html redirect.
	        echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$url.'";';
	        echo '</script>';
	        echo '<noscript>';
	        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
	        echo '</noscript>';
	    }
		
	    die;
	}


	public function urdl($protocol = NULL) {
		if(is_null($protocol)) {
			$protocol = $this->protocol();
		}
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
	
	public function domain($prepend = '') {
		return $prepend.url::$domain[2].'.'.url::$domain[1];
	}
	
	public function protocol() {
		return ($_SERVER['HTTPS'] == 'on')? 'https' : 'http';
	}
	
	
	public function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}
	
	
	public function Link($href, $text = 'none', $rel = false) {
		if($text != 'none') {
			$rel = $rel ? ' rel="'.$rel.'"' : false;
			if(strlen($text) == 0) $text = 'Default Link Text';
			$text = htmlspecialchars($text);
			$href = $this->_strip_sid($href);
			$href = Session::$using == 'cookie' ? $this->_strip_sid($href) : $this->_append_sid($href);
			return "<a href=\"$href\" alt=\"$text\"$rel>$text</a>";
		}
		else {
			return (isset($_COOKIE[Session::Variable]))? $this->_strip_sid($href) : $this->_append_sid($href);
		}
	}
	
	private static function _append_sid($href) {
	
		$href = $this->_strip_sid($href);
		list($link, $get) = explode("?", $href);
		$get = strlen($get) > 0 ? Session::Variable."=".Session::$sid."&".$get : Session::Variable."=".Session::$sid;
		
		// Killed SID appendage.
		return $link;		// ."?".$get;
	}
	
	private static function _strip_sid($href) {
		list($link, $get) = explode("?", $href);
		$gvars = explode('&', $get);
		if(count($gvars) > 0) {
			foreach($gvars as $key => $val) {
				if(strpos($val, Session::Variable.'=') !== false) unset($gvars[$key]);
			}
			$get = implode('&', $gvars);
		}
		return (count($gvars) > 0 && strlen($get) > 0)? $link.'?'.$get : $link;
	}



}
