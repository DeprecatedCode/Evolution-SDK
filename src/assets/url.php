<?php

/**
 * (mini) url class
 * 
 * FUNCTIONALITY ||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
 * 
 * --- Assign a handler.
 * url::Assign(string|%regex|integer, file|::function|object->);
 * 
 * --- Assign a default handler. This is executed at shutdown if no other handler has run.
 * url::Default(file|::function|object->);
 * 
 * --- Redirect to another url.
 * url::ReAssign(string|%regex|integer, url|url{handle} ); // reference the handle with {handle}
 * 
 * --- Label the handles.
 * url::Label("[/]label/label/...");
 * For example, if you ran url::Label('/account/subscription/issue'); you could then access handle 1 with
 * url::Handle('account'); instead of url::Handle(1); So if you have run the label function, accessing /12/14/82
 * will hunt for handlers as if you were accessing /account/subscription/issue. You can run this function as many times
 * as you'd like and you can run it on paths relative to the current handle.
 * 
 * --- Trace the url path to your current or specified handle.
 * url::Trace(null|integer|string);
 * This will return the trace up to the handle which would be returned by url::Handle(); So... if your
 * url is /account/subscription/1 then the trace for url::Trace('subscription'); would be "/account/",
 * and the trace for url::Trace(3); would be /account/subscription/
 * 
 * --- Get the value of a handle
 * url::Handle(null|integer|string);
 * null: returns the current handle
 * integer (positive): returns the handle by # eg /1/2/3/4/5/6/..
 * integer (negative): returns the handle by reverse #: /-6/-5/-4/-3/-2/-1/
 * string (of style "+integer"): returns the value of the handle # relative to the current position.
 * string: returns the value of the handle by label
 * 
 * --- Positioning the handle pointer
 * url::Position(null|integer);
 * null: returns the current position
 * integer: sets the position to whatever handle # specified.
 * 
 * url::Next();
 * Moves the pointer forwards 1. This function also executes every time a handle discovers it's handler.
 * So, if you access /account/post/1234 and it is handled by /account/post.php. url::Handle() would return
 * 1234 and url::Position() would return 3.
 * 
 **/

class url {

	/**
	 * the requested url, eg: /something/else/now
	 *
	 * @var string
	 */
	public static $path = '';
	
	/**
	 * the domain, broken up into an array, eg: array(1 => 'com', 2 => "domain", 3 => 'www')
	 *
	 * @var array
	 */
	public static $domain = array();
	
	/**
	 * The domain. String format
	 *
	 * @var string
	 */
	public static $server = '';

	/**
	 * an array of segments, eg: /something/else/now = array(0 => 'something', 1 => 'else', 2 => 'now')
	 *
	 * @var string
	 */
	public static $segments = array();

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
	public static $referer;

	/**
	 * Current Pointer
	 *
	 * @var integer
	 */
	public static $pointer = 1;
	
	
	public static $portal = false;
	
	/**
	 * Last segment
	 */
	public function last() {
		$c = count($this->segments);
		return $this->segments[$c - 1];
	}
	
	/**
	 * Initialize the url handler.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function initialize() {

		# initialize variables
		self::$path = $_SERVER['REQUEST_URI'];
		self::$referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		self::$domain = self::_parse_domain();
		self::$segments = self::_parse_path();
		
		# define SUBDOMAIN
		$subDomain = !empty(self::$domain[3]) ? self::$domain[3] : '';
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
	public static function assign($segment, $type, $value) {

		# pointer to reassign at end of execution
		$pointer = self::$pointer;
		
		if(strpos($segment, '/')) {
			$segs = explode('/', $segment);

			foreach($segs as $key => $segment) {

				if(self::_match_handle($segment, self::segment($key+1)))
					self::$pointer += 1;
				else {
					self::$pointer = $pointer;
					return false;
				}
			}
			
			self::$pointer -= 1;
		}
		if(!isset(self::$matched[self::$pointer]) && self::_match_handle($segment, self::segment())) {
			switch($type) {
				case 'controller' :
					$file = ROOT_CONTROLLERS.'/'.$value.'.php';
					if(file_exists($file)) {
		            	++self::$pointer;
						include $file;
						self::auto($value);
		            	self::$pointer = $pointer;
		            	self::$matched[$pointer] = true;
						return true;
					}
					return false;
				break;
				case 'actions' :
					++self::$pointer;
            		$ac = self::segment();
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
		            	++self::$pointer;
						include $file;
		            	self::$pointer = $pointer;
		            	self::$matched[$pointer] = true;
						return true;
					}
					return false;
				break;
				case 'interface' :
					$parse = file_exists(SUPER_ROOT_INTERFACE.'/'.$value.'.ixml') ? new Interface_Parser(SUPER_ROOT_INTERFACE.'/'.$value.'.ixml') : new Interface_Parser($value.'.ixml');
					echo (string)$parse->object;
					return true;
				break;
				case 'interface_group' :
					if(is_dir(ROOT_INTERFACE.'/'.$value)) {
						self::$pointer +=1;
						$page = urlencode(self::segment());
						if(!$page) $page = 'index';
						if(file_exists(SUPER_ROOT_INTERFACE.'/'.$value.'/'.$page.'.ixml')){
							$parse = new Interface_Parser($value.'/'.$page.'.ixml');
							$t = microtime(true);
							echo (string)$parse->object;
							//var_dump(microtime(true)-$t);
							return true;
						}elseif(file_exists(ROOT_INTERFACE.'/'.$value.'/'.$page.'.ixml')) {
							$parse = new Interface_Parser($value.'/'.$page.'.ixml');
							$t = microtime(true);
							echo (string)$parse->object;
							//var_dump(microtime(true)-$t);
							return true;
						}
					}
				break;
			}
		}
		self::$pointer = $pointer;
		return false;
	}
	
	
	public static function auto($controller, $prefix = 'controller_') {
		#Only run if a controller hasn't already been run
		if(!isset(self::$matched[self::$pointer])) {
			
			$segment = self::segment();
			$parameters = array_slice(self::$segments, self::$pointer);
			$class = $prefix.$controller;
			$methods = get_class_methods($class);
			
			if(!is_array($methods))die("The class $class doesn't exist.");
			$methods = array_flip($methods);
			$segment = $segment && isset($methods[$segment]) ? $segment : '_default';
			if($segment == '_default' && self::segment()) {
				$parameters = array_slice(self::$segments, self::$pointer-1);				
			}
			$segment = str_replace('-', '_', $segment);
			if(isset($methods[$segment])) {
				$att = new $class;
				call_user_func_array(array($att,$segment), $parameters);
			}
		}
	}
	
	public static function Destroy() {
		$count = 1;
		while(count(self::$controllers) >= $count) {
			unset(self::$controllers[$count]);
			$count++;
		}
	}

	public static function ReAssign($segment, $to)	{
		
		# redirect to new url
		if(self::_match_handle($segment, self::$segments[self::$pointer]))
			self::redirect(self::Link(self::Trace().$to));

	}
	
	
	public static function Position($no = null) {
		if(is_null($no)) return self::$pointer;
		else self::$pointer = $no;
	}
	
	
	public static function Next() {
		++self::$pointer;
	}
	
	
	public static function Prev() {
		--self::$pointer;
	}
	
	public static function Trace($no = false) {
		$no = $no !== false ? $no : self::$pointer;
		
		if($no < 0) {
			$no = $no / -1;	// invert sign
			$no = self::$pointer - $no;
		}
		
		$path = '/';
		foreach(self::$segments as $key => $val) {
			if($key >= $no) { break; } else {
				$path .= $val.'/';
			}
		}
		return $path;
	}

	
	public static function segment($pointer = false) {
		
		# if no pointer has been specified, get the current pointer
		$pointer = $pointer ? $pointer : self::$pointer;
		
		# string "+integer"
		if( is_string($pointer) && substr($pointer, 0, 1) == '+') {
			$pointer = self::$pointer + str_replace('+', '', $pointer);
			return self::$segments[$pointer];			
		}
		
		# string
		elseif( is_string($pointer) ) {
			$labels = array_flip(self::$labels);
			$pointer = $labels[$pointer];
				return self::$segments[$pointer];
		}
		
		# integer (negative)
		elseif( $pointer < 0 ) {
			$pointer = $pointer / -1;	// invert sign
			$pointer = $pointer - 1;	// subtract 1
			$endlevels = array_reverse(self::$segments);
			return $pointer ?  $endlevels[$pointer] : $endlevels[$pointer];
		}
		
		# integer (positive)
		else return isset(self::$segments[$pointer]) ? self::$segments[$pointer] : false;
	}
	
	public static function Label($string, $condition = TRUE) {
		if($condition == FALSE) return FALSE;
		
		$labels = explode("/", $string);
		$output = array();
		if(substr($string, 0, 1) == '/') {
			unset($labels[0]);
			foreach($labels as $key => $val) {
				self::$labels[$key] = $val;
			}
		} else {
			foreach($labels as $key => $val) {
				self::$labels[$key + self::$pointer] = $val;
			}
		}
	}
	
	public static function _load_object($segment, $pointer, array $parameters = array()) {
	
		# check if an object exists
		if(class_exists('Controller_'.$segment)) {
			$object = 'controller_'.$segment;
			self::$controllers[$pointer] = new $object;
			
			$methods = get_class_methods(self::$controllers[self::$pointer - 1]);
			$methods = array_flip($methods);
	
			if(count(self::$segments) == $pointer) {
				
				if(isset($methods['_default'])) {
					call_user_func_array(array(self::$controllers[self::$pointer - 1], '_default'), $parameters);
				}
			}
		}
	}
	
	//Assigns all specially formatted GET variables ( /key:val/ ) to the $_GET superglobal
	public static function _parse_get() {
		$pathComponents = explode('/', self::$path);
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

	public static function _parse_path($uri = '') {
	
		if(empty($uri)) $uri = $_SERVER['REQUEST_URI'];
		
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
		return $path;
	}

	public static function _parse_domain() {
		self::$server = $_SERVER['SERVER_NAME'];
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
		self::$server = $port ? self::$server.":".$port : self::$server;
		return $output;
	}

	public static function _is_function_call($call) {

		return
			strstr($call, '::') ?
			trim(str_replace('::','',$call)) :
			false;

	}
	public static function _match_handle($segmentr, $segment) {

		if(substr($segmentr, 0, 1) == '%') {
			if(match_type(substr($segmentr, 1), $segment)) {
				return true;
			}
		}
		elseif($segment == $segmentr) { #echo $segment.'=='.$segmentr; 
		return true; }

		return false;

	}



	public static function Redirect($to)	{
		
		ob_end_clean();
		
		if(stripos($to, '://') === FALSE) {
			$url = self::protocol(). '://' . self::$server . self::Link($to);
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


	public static function urdl($protocol = NULL) {
		if(is_null($protocol)) {
			$protocol = self::protocol();
		}
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
	
	public static function domain($prepend = '') {
		return $prepend.url::$domain[2].'.'.url::$domain[1];
	}
	
	public static function protocol() {
		return ($_SERVER['HTTPS'] == 'on')? 'https' : 'http';
	}
	
	
	public static function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest");
	}
	
	
	public static function Link($href, $text = 'none', $rel = false) {
		if($text != 'none') {
			$rel = $rel ? ' rel="'.$rel.'"' : false;
			if(strlen($text) == 0) $text = 'Default Link Text';
			$text = htmlspecialchars($text);
			$href = self::_strip_sid($href);
			$href = Session::$using == 'cookie' ? self::_strip_sid($href) : self::_append_sid($href);
			return "<a href=\"$href\" alt=\"$text\"$rel>$text</a>";
		}
		else {
			return (isset($_COOKIE[Session::Variable]))? self::_strip_sid($href) : self::_append_sid($href);
		}
	}
	
	private static function _append_sid($href) {
	
		$href = self::_strip_sid($href);
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
