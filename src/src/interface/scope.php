<?php

/**
 * This is an object that gets attached to a specific XHTML element and manages the data
 * access requests within that element.
 *
 * @package default
 * @author David D. Boskovic
 */
class InterfaceHelper_Scope {
	
	/**
	 * Link to Parent Scope.
	 *
	 * @var string
	 */
	public $parent;
	
	/**
	 * Variable for storing links to any data in this scope.
	 *
	 * @var string
	 */
	public $data;
	
	/**
	 * Indicate the source map var.
	 *
	 * @var string
	 */
	public $source;
	private $source_as = false;
	public $iterator = false;
	
	/**
	 * Indicates the current iteration position.
	 *
	 * @var string
	 */
	public $pointer = false;
	
	/**
	 * Indicates the number of iterations available.
	 *
	 * @var string
	 */
	private $count;
	
	
	public function __construct($parent = false) {
		if(is_object($parent)) $this->parent = $parent;
	}
	
	/**
	 * Resets the Iteration pointer and reloads the source data.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function reset() {
		if(is_object($this->data[$this->iterator]) && method_exists($this->data[$this->iterator], '_scope_rewind')) {
			$this->data[$this->iterator]->_scope_rewind();
			$this->count = $this->data[$this->iterator]->count();
		}
		else $this->count = $this->data[$this->iterator] ? count($this->data[$this->iterator]) : 0;
		$this->pointer = false;
	}
	
	public function get_data() {
		
		$s = $this->source;
		// count only on the first iteration
		if($s)
			return $this->parent->$s;
		else
			return $this->data;
	}
	
	/**
	 * Load data into this scope.
	 *
	 * @param string $source_map 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function source($source_map, $as = false) {
		if($as) $this->source_as = $as;
		else $this->source_as = 'i';
		# if requesting query, load the query results into this scope
		$this->data[$this->source_as] = $this->get($source_map);
		$this->iterator = $this->source_as;
	}
	
	/**
	 * Iterate through the variables in this scope.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function iterate($limit = false) {

		$this->iterator = $this->source_as;
		//var_dump($this->iterator, $this->data[$this->iterator]);
		--$limit;
		if($limit !== false && $limit !== NULL && $this->pointer !== false && $this->pointer >= $limit) return false;
		$this->pointer = $this->pointer === false ? 0 : $this->pointer + 1;
		return $this->pointer >= $this->count ? false : true;
	}
	
	
	public function __get($v) {
		$g =  $this->get($v);
		return $g;
	}
	
	/**
	 * Function for getting any variables in this scope or parent scopes.
	 *
	 * @param string $var 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function get($var_map) {
		//var_dump($var_map);
		if(is_string($var_map) AND strpos($var_map, '%') === 0) $var_map = substr($var_map, 1);
		
		$iterated = false;
		# get the parsed array or parse the string into the proper array
		$allmap = is_string($var_map) ? $this->parse($var_map) : $var_map;
		$filters = $allmap['filters'];
		$map = $allmap['vars'];
		
			$flag_first = false;
		# this is a magic variable if it starts with :
		if(substr($map[0], 0, 1) == ':') {
			// @magic
			switch($map[0]) {
				case ':component':
				case ':com':
				case ':app':
					$source = e::$component->get($map[1]);
					$flag_first = 2;
				break;
				case ':controller':
					$source = e::$controller->get($map[1]);
					$flag_first = 2;
				break;
				case ':session':
					$source = e::$session->data;
					$flag_first = 1;
				break;
				case ':flash':
					$source = e::$session->flashdata($map[1]);
					$flag_first = 2;
				break;
				case ':utility':
					$source = e::$utility->get($map[1]);
					$flag_first = 2;
				break;
				case ':action':
					$source = e::$action->get($map[1]);
					$flag_first = 2;
				break;
				case ':member':
					$source = e::$session->member;
					$flag_first = 1;
				break;
				case ':profile':
					$id = url::$segments[3];
					if(is_numeric($id) && $id > 0) {
						$source = e::$component->users->account($id);
					} else {
						$source = e::$session->member;
					}
					$flag_first = 1;
				break;
				case ':get':
					$source = $_GET;
					$flag_first = 1;
				break;
				case ':url':
					$source = e::$url;
					$flag_first = 1;
				break;
				case ':array':
					$source = e::create();
					$flag_first = 1;
				break;
				
				case ':iterator':
					$source = array('key' => $this->pointer, 'index' => $this->pointer + 1, 'odd' => $this->pointer&1, 'last' => $this->pointer == $this->count-1, 'first' => $this->pointer == 0);
					$flag_first = 1;
				break;
			}
		}
		if(substr($map[0], 0, 1) == '@') {
			$com = substr($map[0],1);
			$source = e::$component->$com;
			$flag_first =1;
			//var_dump($map); die;
		}
		if(!$flag_first) {
			# load the data as the original source.
			$source = $this->data;

			# make sure our first variable exists in this scope or look for it upstream
			if(is_string($map[0]) AND strpos($map[0],"'") === 0) {
				return(trim($map[0],"'"));
			}
			if(is_string($map[0]) AND is_numeric($map[0])) {
				return($map[0]);
			}
			if(is_string($map[0])) {
				if(!isset($this->data[$map[0]]))
					return $this->parent ? $this->parent->get($allmap) : false;
			} else {
				e::$error->fault(100, 'ixml_scope_no_func_call');
			}
		}
		# loop through each map
		foreach($map as $i => $var) {
			if($map[0] == ':get' && $map[1] == 'search') {
				//echo(' | i:'.$i);
				//echo(' | flag: '.$flag_first);
			}
			if($flag_first && $i < $flag_first) continue;
			//if($map[0] == ':get' && $map[1] == 'search') echo(' | var: '.$var);
			
			# let's not waste time and energy of we're not going anywhere
			if(!$source) return NULL;
			
			# if this is a function
			if(is_array($var))	{
				if(is_object($source)) {
					if(method_exists($source, $var['func']))
						$source = call_user_func_array(array($source,$var['func']), $var['args']);
					elseif(method_exists($source, '__call'))				
						$source = call_user_func_array(array($source,$var['func']), $var['args']);					
				}
			}
			
			# if we're looking for a variable in a source object
			elseif(is_object($source)) {
				if(isset($source->$var))
					$source = $source->$var;
				elseif(!is_null($var) && method_exists($source, $var)) {
					$source = $source->$var();
				}
				elseif(!is_null($var) && method_exists($source, '__call')) {					
					$source = $source->$var();
				}
			}
			
			# if we're looking for a variable in an array
			elseif(is_array($source)) {
				if($this->pointer !== false && $map[0] == $this->iterator && !$iterated) {
					//var_dump($var,$source[$this->iterator][$this->pointer]->$var);
					//var_dump($this->iterator,$this->pointer, $source);
					$iterated = true;
					//$c = false;
					//if(is_object($source[$this->iterator])) {  $c = true;var_dump($this->pointer); }
					$source = is_object($source[$this->iterator]) ? $source[$this->iterator]->_scope_by_pos($this->pointer) : $source[$this->iterator][$this->pointer];
					
					//if($c) { var_dump($source); }
				}
				else {
					
					//if($map[0] == ':get' && $map[1] == 'search') var_dump($source);
					$source = @$source[$var];
				}
			}
			else
				$source = false;
		}
		//$this->data = $source;
		//var_dump($var_map, $source, $this->data);
		# execute any piped filters
		if($filters)
			foreach($filters as $filter) {
				if(is_array($filter)) {
					e::$filter->get($source, $filter['func'], $filter['args']);
				} else {
					e::$filter->get($source, $filter, array());
					//echo 'test';
				}
			}
			
		return $source;
		
		
		
		
		
		# if our source is a variable in a parent scope
		if($this->source) {
			//var_dump($this->pointer);
			$as = $this->source_as ? $this->source_as.'.' : false;			
			$parent_var = $var_map;
			$s = $this->source;
			if($as && strpos($var_map, $as) === 0) {
				$nvp = substr($var_map, strlen($as));
				$pp = $this->pointer !== false ? $this->parent->$s : false;
				return $this->pointer !== false ? $this->map($nvp, $pp[$this->pointer]) : $this->map($nvp, $this->parent->$s);				
			} else {
				$pp = $this->pointer !== false ? $this->parent->$s : false;
				//var_dump($pp[$this->pointer]);
				return $this->pointer !== false ? $this->map($var_map, $pp[$this->pointer]) : $this->map($var_map, $this->parent->$s);
				return $this->parent->$s->$var_map; //$this->map($var_map, $this->parent->$s);				
			}
		} else {
			//debug_print_backtrace();
			//var_dump($this->data);
			//echo '(mapped)';
			//var_dump($this->pointer);
			$as = $this->source_as ? $this->source_as.'.' : false;
			$using_as = strpos($var_map, $as) === 0;
			$nvp = $using_as ? substr($var_map, strlen($as)) : $var_map;
			if($using_as) {				
				$t = $this->pointer !== false ? $this->map($nvp, $this->data[$this->pointer]) : $this->map($nvp, $this->data);
			} else {
				
					$pp = $this->pointer !== false ? $this->parent->$s : false;
					
					//var_dump($pp[$this->pointer]);
					return $this->pointer !== false ? $this->map($var_map, $pp[$this->pointer]) : $this->map($var_map, $this->parent->$s);
			}
			return $t;
		}
	}
	private function extract_vars($content) {
		
		if(strpos($content, '{') === false) return array();
		// parse out the variables
		preg_match_all(
			"/{([\w:|.\,\(\)\/\-\% ]+?)}/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	private function extract_subvars($content) {
		
		if(strpos($content, '[') === false) return array();
		// parse out the variables
		preg_match_all(
			"/\[([\w:|.\,\(\)\/\-\% ]+?)\]/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	private function extract_funcs($content) {
		if(strpos($content, '(') === false) return array();
		// parse out the variables
		preg_match_all(
			"/([\w]+?)\(([\w:|.\,=\(\)\/\-\% ]*?)\)/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = array('func' => $var[1], 'string' => $var[0], 'args' => explode(',', $var[2]));
		}
		
		return $vars;
	}
	private function parse($var) {
		// @debug for testing
		// $var = "var.var2().name|test";
		if(strpos($var,' ? ') !== false) {
			list($cond, $result) = explode(' ? ', $var);
			$else = false;
			
			if(strpos($result,' : ') !== false) {
				list($result, $else) = explode(' : ', $result);
			}
			if(strpos($cond,' == ') !== false) {
				list($cond, $compare) = explode(' == ', $cond);
				$val = $this->get($cond);
				$cval = $this->get($compare);
				//var_dump($val, $cval, $val == $cval);
				if($val == $cval) $var = $result;
				else $var = $else;
			}
			elseif(strpos($cond,' != ') !== false) {
				list($cond, $compare) = explode(' != ', $cond);
				$val = $this->get($cond);
				$cval = $this->get($compare);
				if($val != $cval) $var = $result;
				else $var = $else;
			}
			else {
				$val = $this->get($cond);
				if($val) $var = $result;
				else $var = $else;
			}
			
		}
		# extract and replace any sub-variables that are in this string
		$extract_vars = $this->extract_vars($var);
		if($extract_vars)
			foreach($extract_vars as $rv) {
				$val = (string) $this->get($rv);
				$var = str_replace('{'.$rv.'}', $val, $var);
			}
		
		# extract and replace any sub-variables that are in this string
		$extract_subvars = $this->extract_subvars($var);
		if($extract_subvars) {
			foreach($extract_subvars as $rv) {
				$val = (string) $this->get($rv);
				$var = str_replace('['.$rv.']', $val, $var);
			}
			
		}
		# extract and replace any functions in this string
		$ef = $this->extract_funcs($var);
		if($ef)
			foreach($ef as $k => $f) {
				$ef[$k]['key'] = '%F'.$k;
				$var = str_replace($f['string'],'%F'.$k,$var);
			}

		# parse out our piped filters
		if(strpos($var, '|') !== false) {
			$a = explode('|', $var);

			$var = strlen($a[0]) > 0 ? $a[0] : false;
			$filters = array_slice($a, 1);
		}
		else {
			$filters = array();
		}
		
		# get the variable and throw the function arrays into it if necessary
		$vars = explode('.', $var);
		foreach($vars as &$v) {
			if(substr($v,0,2) == '%F')
				$v = $ef[substr($v,2)];
		}
		
		# get the variable we are actually manipulating
		/*if(strlen($var) > 0)
			$this->map($var);
		else
			$this->data = $this->source;*/

		# execute any piped filters
		if($filters)
			foreach($filters as &$filter) {
				if(substr($filter,0,2) == '%F')
					$filter = $ef[substr($filter,2)];
			}
		return array('vars' => $vars, 'filters' => $filters);
	}

	public function map($var, $source = false) {
		//var_dump($var);
		if(strpos($var,'%') === 0) $var = substr($var,1);
		$this->parse($var); return false;
		# make sure we're not being passed an empty variable
		if(!$var) return false;
		
		# parse out our piped filters
		if(strpos($var, '|') !== false) {
			$a = explode('|', $var);
			$var = $a[0];
			$filters = array_slice($a, 1);
		}
		else
			$filters = false;
		
		$levels = explode('.', $var);
		$source = $source ? $source : $this->source;
		if($var == '*') return $source;
		$c = count($levels);
		# access an application
		if($levels[0] == '::app' || $levels[0] == '::application') {
			$source = e::app($levels[1]);
			unset($levels[0], $levels[1]);
		}
		foreach($levels as $var) {
			if(strpos($var, '(') !== false)	{
				$func = $this->_parse_func($var);
				if(is_object($source)) {
					if(method_exists($source, $func[0]))
						$source = call_user_func_array(array($source,$func[0]), $func[1]);
					elseif(method_exists($source, '__call'))				
						$source = call_user_func_array(array($source,$func[0]), $func[1]);					
				}
			}	
			elseif(is_object($source)) {
				if(isset($source->$var))
					$source = $source->$var;
				elseif(method_exists($source, $var)) {
					$source = $source->$var();
				}
				elseif(method_exists($source, '__call')) {					
					$source = $source->$var();
				}
			}
			elseif(is_array($source))
				$source = $source[$var];
			else
				$source = false;
		}
		//$this->data = $source;
		
		# execute any piped filters
		if($filters)
			foreach($filters as $filter) {
				list($filter, $vars) = $this->_parse_filter($filter);
				e::filter($source, $filter, $vars);
			}
		
		return $source;
	}

	private function _parse_func($filter) {		
			$m[0] = substr($filter, 0, strpos($filter,'('));
			$vars = substr($filter, strpos($filter,'('));
			$vars = trim($vars, '()');
			$vars = explode(',', $vars);
			//var_dump($filter);
			$m[1] = $vars;
			return $m;
	}

	private function _parse_filter($filter) {
		if(strpos($filter, '(') !== false) {
			$m[0] = substr($filter, 0, strpos($filter,'('));
			$vars = substr($filter, strpos($filter,'('));
			$vars = trim($vars, '()');
			$vars = explode(',', $vars);
			$m[1] = $vars;
		}
		else {
			$m[0] = $filter;
			$m[1] = array();
		}
		return $m;		
	}

}
