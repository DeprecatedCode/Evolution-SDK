<?php


/**
 * Every html tag gets a new instance of this object in an interface.
 *
 * @package default
 * @author David D. Boskovic
 */
class InterfaceBuilder {
	
	/**
	 * Tag Names as Keys
	 *
	 * @var string
	 */
	public static $tags = array( 'a' => 0, 'abbr' => 1, 'acronym' => 2, 'address' => 3, 'applet' => 4, 'area' => 5, 'b' => 6, 'base' => 7, 'basefont' => 8, 'bdo' => 9, 'big' => 10, 'blockquote' => 11, 'body' => 12, 'br' => 13, 'button' => 14, 'canvas' =>0, 'caption' => 15, 'center' => 16, 'cite' => 17, 'code' => 18, 'col' => 19, 'colgroup' => 20, 'dd' => 21, 'del' => 22, 'dfn' => 23, 'dir' => 24, 'div' => 25, 'dl' => 26, 'dt' => 27, 'em' => 28, 'fieldset' => 29, 'font' => 30, 'form' => 31, 'frame' => 32, 'frameset' => 33, 'head' => 34, 'h1' => 35, 'h2' => 36, 'h3' => 37, 'h4' => 38, 'h5' => 39, 'h6' => 40, 'hr' => 41, 'html' => 42, 'i' => 43, 'iframe' => 44, 'img' => 45, 'input' => 46, 'ins' => 47, 'kbd' => 48, 'label' => 49, 'legend' => 50, 'li' => 51, 'link' => 52, 'map' => 53, 'menu' => 54, 'meta' => 55, 'noframes' => 56, 'noscript' => 57, 'object' => 58, 'ol' => 59, 'optgroup' => 60, 'option' => 61, 'p' => 62, 'param' => 63, 'pre' => 64, 'q' => 65, 's' => 66, 'samp' => 67, 'script' => 68, 'select' => 69, 'small' => 70, 'span' => 71, 'strike' => 72, 'strong' => 73, 'style' => 74, 'sub' => 75, 'sup' => 76, 'table' => 77, 'tbody' => 78, 'td' => 79, 'textarea' => 80, 'tfoot' => 81, 'th' => 82, 'thead' => 83, 'title' => 84, 'tr' => 85, 'tt' => 86, 'u' => 87, 'ul' => 88, 'var' => 89, 'embed' => 90,'header'=>91,'aside'=>92,'article'=>93,'nav'=>94,'section'=>95,'footer'=>96,'q'=>97,'mark'=>0,'');
	public static $quick_tags = array( 'area' => 0, 'base' => 1, 'basefont' => 2, 'br' => 3, 'col' => 4, 'frame' => 5, 'hr' => 6, 'img' => 7, 'input' => 8, 'link' => 9, 'meta' => 10, 'param' => 11,'embed' => 12);
	
	/**
	 * Map IXML Elements to their proper interpreters
	 *
	 * @author David D. Boskovic
	 */
	public static $ixml_special = array(
		'ixml:interface' => 'InterfaceHelper_IXML_Interface',
		'ixml:configure' => 'InterfaceHelper_IXML_Configure',
		'ixml:xhtml' => 'InterfaceHelper_IXML_XHTML',
		'ixml:form' => 'InterfaceHelper_IXML_Form',
		'ixml:snippet' => 'InterfaceHelper_IXML_Snippet',
		'ixml:switch' => 'InterfaceHelper_IXML_Switch',
		'ixml:include' => 'InterfaceHelper_IXML_Include',
		'ixml:module' => 'InterfaceHelper_IXML_Module',
		'ixml:if' => 'InterfaceHelper_IXML_If',
		'ixml:var' => 'InterfaceHelper_IXML_Var',
		'ixml:dropdown' => 'InterfaceHelper_IXML_Dropdown',
	);
	

	
	/**
	 * An array of links to the child elements.
	 *
	 * @var array
	 */	
	public $children = array();
	
	/**
	 * An array of the element's attributes and values.
	 *
	 * @var array
	 */
	public $attr = array();
	
	/**
	 * An array of data which assists PHP in accessing specific elements.
	 *
	 * @var array
	 */
	protected $index = array();
	
	/**
	 * The element name. ie: div
	 *
	 * @var string
	 */
	public $el = false;
	public $fel = false;
	
	/**
	 * A variable linking straight to the parent html element.
	 *
	 * @var InterfaceHelper Object
	 */
	public $_ = false;
	
	/**
	 * Current child element count.
	 *
	 * @var string
	 */
	protected $ec = 0;
	
	

	
	/**
	 * Initialize a new element object.
	 *
	 * @param string $el 
	 * @param string $parent 
	 * @author David D. Boskovic
	 */
	public function __construct($el = false, $parent = false) {
		$this->fel = $el;
		$this->el = isset(self::$tags[$el]) || isset(self::$ixml_special[$el]) ? $el : false;
		$this->_ = $parent;
	}
	
	/**
	 * Returns true if this element has any children.
	 *
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function _has_content() {
		return (count($this->children) > 0) ? true : false;
	}
	
	/**
	 * Utility Function: Convert Attributes to HTML String
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function utility_get_attributes_html() {
		$html = '';
		foreach($this->attr as $attr => $value) {			
			$vars = $this->extract_vars($value);
			if(substr($attr,0,1) == '_' || substr($attr,0,5) == 'ixml:') continue;
			$html .= " $attr=\"$value\"";
		}
		return $html;
	}
	
	protected function extract_vars($content) {
		# if there are no variables, don't try regex
		if(strpos($content,'{') === false) return false;
		
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
	
	/**
	 * Add/modify attributes on the current element by passing an array of key/values.
	 *
	 * @param string $array 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function _attr($array) {
		//var_dump($array);
		if(is_array($array))
		foreach($array as $attr => $val) {
			if(substr($attr, 0, 4) == 'ixml' && method_exists($this, 'ixml_'.substr($attr,5))) { 
				$c = 'ixml_'.substr($attr,5); $this->$c($val);
			} 
			else {
				$c = 'attr_'.$attr;
				if(method_exists($this, $c)) $this->$c($val);
				$this->attr[$attr] = $val;
			}
		}
		return $this;
	}
	
	/**
	 * Utility Function: Create New Child Element
	 *
	 * @param string $el 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function _el($el) {		
		++$this->ec;		
		return ($this->children[$this->ec] = new InterfaceBuilder($el, $this));
	}
	
	/**
	 * Add an orphan DOM element as a child of this object.
	 *
	 * @param string $object 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function _hardwire($object) {
		$object->_ = $this;
		$object->_data = $this->_data;
		++$this->ec;		
		$this->children[$this->ec] = $object;
		return $this;
	}

	
	/**
	 * Undeclared Variable Call
	 * 
	 * Whenever an undeclared variable name is called on this object, ie: $el->div, this function
	 * will return an instance of the new object after creation.
	 *
	 * @param string $var 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function __get($var) {
		if($var == '_') {
			if(is_object($this->_)) return $this->_; 
		}
		else {			
			++$this->ec;				
			$this->children[$this->ec] = new InterfaceBuilder($var, $this);
		}
		return $this->children[$this->ec];
	}
	
	/**
	 * Undeclared Function Call
	 * 
	 * Whenever an undeclared function name is called on this object, ie: $el->attr(), this function
	 * will add or modify an attribute and then return a reference to this object.
	 *
	 * @param string $method 
	 * @param string $args 
	 * @return void
	 * @author David D. Boskovic
	 */
	public function __call($method, $args) {
		$tags =& self::$tags;
		if(isset($tags[$method])) {
			if(isset($args[0]) && isset($this->index[$args[0]])) {
				return $this->index[$args[0]];
			} elseif(isset($args[0])) {
				++$this->ec;
				$this->children[$this->ec] = new InterfaceBuilder($method, $this);
				$this->index[$args[0]] =& $this->children[$this->ec];
			} else {		
				++$this->ec;		
				$this->children[$this->ec] = new InterfaceBuilder($method, $this);
			}
			return $this->children[$this->ec];
		}
		elseif($method == '_html' || $method == '_text') {			
			++$this->ec;
			$this->children[$this->ec] = $method == '_text' ? mb_convert_encoding(htmlspecialchars($args[0], ENT_QUOTES, 'UTF-8'),'HTML-ENTITIES', 'UTF-8') : mb_convert_encoding($args[0],'HTML-ENTITIES', 'UTF-8');
			//$this->children[$this->ec] = $method == '_text' ? htmlspecialchars($args[0], ENT_QUOTES, 'UTF-8') : $args[0];
			return $this;
		}
		else {
			if($args[0]) $this->attr[$method] = $args[0];
			return $this;
		}
	}
	

	
	public function generate() {
		
		# loop through attributes and extract special IXML vars
		$attrs = array();
		$ixml = array();
		foreach ($this->attr as $key  => $attr) {
			if(strpos($key, 'ixml') === 0) {
				$ixml[substr($key,5)] = $attr;
			}
			else {
				if(strpos($this->fel, 'ixml') === 0)
					$ixml[$key] = $attr;
				else
					$attrs[$key] = $attr;
			}
		}
		$a = array(
			'el' => $this->fel,
			'attr' => $attrs,
			'ixml' => $ixml,
			'nodes' => array()
		);
		//var_dump($this->children[1]->fel);
		foreach($this->children as $key => $child) {
			$a['nodes'][] = is_object($child) ? $child->generate() : array('text' => $child);
		}
		return $a;
	}
	
	/**
	 * Generate a string of valid output XHTML.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function __toString() {
		
		if(isset($this->attr['ixml:loop_source'])) {
				$var = $this->attr['ixml:loop_source'];
				list($source, $as) = explode(' as ', $var);	

				$vars = $this->extract_vars($source);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$source = str_replace('{'.$var.'}', $data_response, $source);				
					}				
				}

				# add the variables to the new element source object
				$this->_data()->data[$as] = $this->_data()->get($source);
		}
		if(isset($this->attr['ixml:highlight_page'])) {
			
			$var = $this->attr['ixml:highlight_page'];
			if(strpos($var, ':') !== false) {
				$vars = $this->extract_vars($var);
				if($vars) {
					foreach($vars as $v) {
						$data_response = ($this->_data()->$v);	
						$var = str_replace('{'.$v.'}', $data_response, $var);				
					}				
				}
			}
			list($pages, $class) = explode(')', substr($var, strpos($var,'(')+1));
			list($match) = explode(':', $var);
			
			$class = trim($class);
			$pages = explode(',',$pages);
			$sel = false;
			foreach($pages as $page) {
				$page = trim($page);
				if($match AND $match == $page)
					$sel = true;
				elseif(strpos($_SERVER['REQUEST_URI'], $page) === 0)
					$sel = true;
			}
			if($sel) {
				$this->attr['class'] .= ' '.$class;
			}
		}
		$html = '';
		$el = $this->el;
		$hc = $this->_has_content();
		if($el && $this->loop_type != 'self') {			
			$html .= "<$el".$this->utility_get_attributes_html().($hc || (!isset(self::$quick_tags[$el]) && !$hc) ? '>' : ' />');
		}
		if($this->is_loop) {
			$this->_data()->reset();
			while($this->_data()->iterate($this->attr['ixml:limit'])) {
				$parsed_children = array();
				if($this->loop_type == 'self') {
					if($el) {			
						$html .= "<$el".$this->utility_get_attributes_html().($hc || (!isset(self::$quick_tags[$el]) && !$hc) ? '>' : ' />');
					}					
				}
				if(count($this->_data_requests) > 0) {			
					foreach($this->_data_requests as $data_request => $indexes) {
						$data_response = ($this->_data()->$data_request);
						foreach($indexes as $index => $val) {
							$parsed_children[$index] = str_replace('{'.$data_request.'}', $data_response, ($parsed_children[$index] ? $parsed_children[$index] : $this->children[$index]));
						}
					}
				}
				else {
				}
				foreach($this->children as $key => $child) {
					if(!is_object($child)) {
						$html .= ' '.$parsed_children[$key].' ';
					} else {
						$html .= (string) $child;
					}
				}
				
				if($this->loop_type == 'self') {
					if($el) {			
						$html .= $hc ? "</$el>" : "";
						$html .= !$hc && !isset(self::$quick_tags[$el]) ? "</$el>" : "";
					}					
				}
			}
		} else {				
			if(count($this->_data_requests) > 0) {			
				foreach($this->_data_requests as $data_request => $indexes) {
					$data_response = ($this->_data()->$data_request);
					//var_dump($data_request);
					//var_dump($indexes);
					foreach($indexes as $index => $val) {
						$parsed_children[$index] = str_replace('{'.$data_request.'}', $data_response, ($parsed_children[$index] ? $parsed_children[$index] : $this->children[$index]));
					}
				}	
			}	
			foreach($this->children as $key => $child) {
				if(!is_object($child)) {
					$html .= ''.(isset($parsed_children[$key]) ? $parsed_children[$key] : $child).'';
				} else {
					$html .= ' '.((string) $child).' ';
				}
			}
		}

		if($el && $this->loop_type != 'self') {			
			$html .= $hc ? "</$el>" : "";
			$html .= !$hc && !isset(self::$quick_tags[$el]) ? "</$el>" : "";
		}
		return $html;
	}
	
	public function is_positive($val) {
		//var_dump($val);
		if(!$val || $val== 'false' || $val=='no' || $val == '0') return false;
		if($val || $val== 'true' || $val=='yes' || $val == '1') return true;
		return false;
	}
}