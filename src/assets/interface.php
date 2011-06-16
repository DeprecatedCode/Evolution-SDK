<?php

# include the required assets
include('interface/scope.php');
include('interface/parser.php');
include('interface/parser.new.php');

# load the ixml modules
include('interface/ixml/interface.php');
include('interface/ixml/configure.php');
include('interface/ixml/xhtml.php');
include('interface/ixml/form.php');
include('interface/ixml/snippet.php');
include('interface/ixml/switch.php');
include('interface/ixml/include.php');
include('interface/ixml/module.php');
include('interface/ixml/if.php');
include('interface/ixml/var.php');
include('interface/ixml/dropdown.php');
include('interface/ixml/select.php');
include('interface/ixml/template.php');
include('interface/ixml/state.php');
include('interface/ixml/for.php');
include('interface/ixml/extend.php');
/**
 * Every html tag gets a new instance of this object in an interface.
 *
 * @package default
 * @author David D. Boskovic
 */
class InterfaceHelper {
	
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
		'ixml:select' => 'InterfaceHelper_IXML_Select',
		'ixml:xhtml' => 'InterfaceHelper_IXML_XHTML',
		'ixml:form' => 'InterfaceHelper_IXML_Form',
		'ixml:snippet' => 'InterfaceHelper_IXML_Snippet',
		'ixml:switch' => 'InterfaceHelper_IXML_Switch',
		'ixml:include' => 'InterfaceHelper_IXML_Include',
		'ixml:module' => 'InterfaceHelper_IXML_Module',
		'ixml:if' => 'InterfaceHelper_IXML_If',
		'ixml:else' => 'InterfaceHelper_IXML_Else',
		'ixml:var' => 'InterfaceHelper_IXML_Var',
		'ixml:dropdown' => 'InterfaceHelper_IXML_Dropdown',
		'ixml:template' => 'InterfaceHelper_IXML_Template',
		'ixml:state' => 'InterfaceHelper_IXML_State',
		'ixml:for' => 'InterfaceHelper_IXML_FOR',
		'ixml:extend' => 'InterfaceHelper_IXML_Extend',
		'ixml:extend_here' => 'InterfaceHelper_IXML_ExtendHere',
	);
	
	public static $iconic = array(
		'home' 					=> '!',
		'at'					=> '@',
		'quote' 				=> '"',
		'quote-alt' 			=> '\"',
		'arrow-up'				=> '3',
		'arrow-right'			=> '4',
		'arrow-bottom'			=> '5',
		'arrow-left'			=> '6',
		'arrow-up-alt'			=> '#',
		'arrow-right-alt'		=> '$',
		'arrow-bottom-alt'		=> '%',
		'arrow-left-alt'		=> '^',
		'move'					=> '9',
		'move-vertical'			=> '8',
		'move-horizontal'		=> '7',
		'move-alt'				=> '(',
		'move-vertical-alt' 	=> '*',
		'move-horizontal-alt'	=> '&',
		'cursor'				=> ')',
		'plus'					=> '+',
		'plus-alt'				=> '=',
		'minus'					=> '-',
		'minus-alt'				=> '_',
		'new-window'			=> '1',
		'dial'					=> '2',
		'lightbulb'				=> '0',
		'link'					=> '/',
		'image'					=> '?',
		'article'				=> '>',
		'read-more'				=> '.',
		'headphones'			=> ',',
		'equalizer'				=> '<',
		'fullscreen'			=> ':',
		'exit-fullscreen'		=> ';',
		'spin'					=> '[',
		'spin-alt'				=> '{',
		'moon'					=> ']',
		'sun'					=> '}',
		'map-pin'				=> '\\',
		'pin'					=> '|',
		'eyedropper'			=> '~',
		'denied'				=> '`',
		'calendar'				=> 'a',
		'calendar-alt'			=> 'A',
		'bolt'					=> 'b',
		'clock'					=> 'c',
		'document'				=> 'd',
		'book'					=> 'e',
		'book-alt'				=> 'E',
		'magnifying-glass'		=> 'f',
		'tag'					=> 'g',
		'heart'					=> 'h',
		'info'					=> 'i',
		'chat'					=> 'j',
		'chat-alt'				=> 'J',
		'key'					=> 'k',
		'unlocked'				=> 'l',
		'locked'				=> 'L',
		'mail'					=> 'm',
		'mail-alt'				=> 'M',
		'phone'					=> 'n',
		'box'					=> 'o',
		'pencil'				=> 'p',
		'pencil-alt'			=> 'P',
		'comment'				=> 'q',
		'comment-alt'			=> 'Q',
		'rss'					=> 'r',
		'star'					=> 's',
		'trash'					=> 't',
		'user'					=> 'u',
		'volume'				=> 'v',
		'mute'					=> 'V',
		'cog'					=> 'w',
		'cog-alt'				=> 'W',
		'x'						=> 'x',
		'x-alt'					=> 'X',
		'check'					=> 'y',
		'check-alt'				=> 'Y',
		'beaker'				=> 'z',
		'beaker-alt'			=> 'Z',
		//Custom Iconic Classes
		'e_members_account'		=> 'u',
		'e_community_team'		=> ';',
		'e_community_project'	=> 's',
		'e_community_charity'	=> 'A',
		'e_tag'					=> 'g',
		'e_field'				=> 's'
	);
	
	/**
	 * Exclude IXML Elements from rendering
	 *
	 * @author David D. Boskovic
	 */
	public static $ixml_exclude = array(
		'?xml' => false
	);	
	/**
	 * This maintains information during the iteration of an interface loop.
	 *
	 * @var array
	 */
	public $loop_type = 'content';
	public $is_loop = false;
	
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
	 * Information access layer.
	 *
	 * @var string
	 */
	public $_data;
	
	/**
	 * All parsed data requests
	 *
	 * @var string
	 */
	protected $_data_requests;
	
	public $_extending_content = false;
	
	
	/**
	 * Initialize a new element object.
	 *
	 * @param string $el 
	 * @param string $parent 
	 * @author David D. Boskovic
	 */
	public function __construct($el = false, $parent = false) {
		$this->fel = $el;
		$this->el = isset(self::$tags[$el]) || isset(self::$ixml_special[$el]) || strpos($el, 'fb') > -1 ? $el : false;
		$this->el = isset(self::$ixml_exclude[$el]) ? false : $this->el;
		$this->_ = $parent; 
		if(!$parent) {
			$this->_data = new InterfaceHelper_Scope;
		}
		else {
			$this->_data = false;
		}
		if(class_exists('Interface_Parser')) {
			if(Interface_Parser::$callback) {
				call_user_func(Interface_Parser::$callback,'startup', $this, Interface_Parser::$callback_data);
			}
		}
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
	
	
	public function _extending_content() {
		if(!$this->_extending_content AND $this->_) return $this->_->_extending_content();
		else return $this->_extending_content;
	}
	
	/**
	 * Utility Function: Convert Attributes to HTML String
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function utility_get_attributes_html() {
		
			$protocol = empty($_SERVER['HTTPS'])? 'http': 'https';
		$html = '';
		foreach($this->attr as $attr => $value) {			
			$vars = $this->extract_vars($value);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$value = str_replace('{'.$var.'}', $data_response, $value);				
				}				
			}
			
			if(($attr == 'href' || $attr == 'action' || $attr == 'src') && (strpos($value, '://') > 0 && (strpos($value, 'http://') !== 0 || strpos($value, 'https://') !== 0))) {
				if(strpos($value, '://') > 0) {
					$access_key = substr($value, 0, strpos($value, '://'));
				}
				$dir = @e::$env['http_path'] ? e::$env['http_path'] : '/';
				$portal = e::$url->portal;
				switch($access_key) {
					case 'static':
						$value = MODE_DEVELOPMENT ? str_replace('static://','http://static.momentumapp.dev/', $value) : str_replace('static://',$protocol.'://assets.momentumapp.co/', $value);
						//$value =str_replace('static://','http://assets.momentumapp.co/', $value);
					break;
					case 'view' :
						$value = str_replace($access_key.'://',e::$url->view_path, $value);
					break;
					case 'protocol' :
						$value = str_replace($access_key.'://',$protocol.'://', $value);
					break;
					case 'http' :
					case 'https' :
						$value = $value;
					break;
					default :
						$value = str_replace($access_key.'://',$dir.$portal.'/', $value);
					break;
				}
				//$value = str_replace('^^/',$dir.$portal.'/', $value);
				//$value = str_replace('^/',$dir, $value);
			}
			
			if(substr($attr,0,1) == '_' || substr($attr,0,1) == ':' || substr($attr,0,5) == 'ixml:') continue;
			if(strlen($value) > 0) $html .= " $attr=\"$value\"";
		}
		return $html;
	}
	
	
	public function utility_get_class_html() {
		$html = '';
		$value = (string) @$this->attr['class'];			
			$vars = $this->extract_vars($value);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$value = str_replace('{'.$var.'}', $data_response, $value);				
				}				
			}
		
		return $value;
	}
	
	public function extract_vars($content) {
		# if there are no variables, don't try regex
		if(strpos($content,'{') === false) return false;

		// parse out the variables
		preg_match_all(
			"/{([\w:;|.\,\(\)\/\-\%&  \[\]\?'=]+?)}/", //regex
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
		
		if(class_exists('Interface_Parser')) {
			if(Interface_Parser::$callback) {
				call_user_func(Interface_Parser::$callback,'attr', $this, Interface_Parser::$callback_data);
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
		return ($this->children[$this->ec] = new InterfaceHelper($el, $this));
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
		//$object->_data = NULL;
		++$this->ec;		
		$this->children[$this->ec] = $object;
		return $this;
	}
	
	public function _data() {
		if($this->_data) return $this->_data;
		else return $this->_->_data();
	}
	
	public function _children($el = false, $first = 0) {
		if(!$el) return array();
		if(strpos($el,'#') !== false)
			list($el, $id) = explode('#', $el);
		
		if(strpos($el,'.') !== false)
			list($el, $class) = explode('.', $el);
		$match = 1;
		$results = array();
		foreach($this->children as $child) {
			if(is_string($child)) continue;
			
			if(isset($id)) {
				if(!(isset($child->attr['id']) AND $child->attr['id'] == $id)) $match = 0;
			}
			if(isset($class)) {
				if(!(isset($child->attr['class']) AND in_array(explode(' ',$child->attr['class']), $id))) $match = 0;	
			}
			if($el) {
				if($child->fel != $el) $match = 0;
			}
			if($match) $results[] = $child;
			$match = 1;
		}
		return $first ? @$results[0] : $results;
	}
	
	public function _delete($el = false, $first = 0) {
		if(!$el) return array();
		if(strpos($el,'#') !== false)
			list($el, $id) = explode('#', $el);
		
		if(strpos($el,'.') !== false)
			list($el, $class) = explode('.', $el);
		$match = 1;
		$results = array();
		foreach($this->children as $key => $child) {
			if(is_string($child)) continue;
			
			if(isset($id)) {
				if(!(isset($child->attr['id']) AND $child->attr['id'] == $id)) $match = 0;
			}
			if(isset($class)) {
				if(!(isset($child->attr['class']) AND in_array(explode(' ',$child->attr['class']), $id))) $match = 0;	
			}
			if($el) {
				if($child->fel != $el) $match = 0;
			}
			if($match) unset($this->children[$key]);
			$match = 1;
		}
		return true;
	}
	public function _find($el = false, $first = 0, $callback = false) {
		$tel = $el;
		if(!$el) return array();
		if(strpos($el,'#') !== false)
			list($el, $id) = explode('#', $el);
		
		if(strpos($el,'.') !== false)
			list($el, $class) = explode('.', $el);
		$match = 1;
		$results = array();
		foreach($this->children as $child) {
			if(is_string($child)) continue;
			
			if(isset($id)) {
				if(!(isset($child->attr['id']) AND $child->attr['id'] == $id)) $match = 0;
			}
			if(isset($class)) {
				//if(isset($class)) var_dump($class, in_array($id,explode(' ',$child->attr['class'])));
				if(!(isset($child->attr['class']) AND ($child->attr['class'] == $class || strpos($child->attr['class']," $class\"") !== false || strpos($child->attr['class']," $class ") !== false || strpos($child->attr['class'],"$class ") === 0))) $match = 0;
				
				//if(!(isset($child->attr['class']) AND in_array($id,explode(' ',$child->attr['class'])))) $match = 0;	
			}
			if($el) {
				if($child->fel != $el) $match = 0;
			}
			$cm = $child->_find($tel);
			if($match) {
				if(is_callable($callback)) call_user_func($callback, $child);
				$results[] = $child;	
			}
			
			$results = array_merge($results, $cm);
			$match = 1;
		}
		//var_dump($first);
		return $first ? @$results[0] : $results;
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
			if(isset(self::$ixml_special[$var])) {
				$c = self::$ixml_special[$var];
				$this->children[$this->ec] =  new $c($var, $this);				
			} else {				
				$this->children[$this->ec] = new InterfaceHelper($var, $this);
			}
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
				$this->children[$this->ec] = new InterfaceHelper($method, $this);
				$this->index[$args[0]] =& $this->children[$this->ec];
			} else {		
				++$this->ec;		
				$this->children[$this->ec] = new InterfaceHelper($method, $this);
			}
			return $this->children[$this->ec];
		}
		elseif($method == '_html' || $method == '_text') {			
			++$this->ec;
			if(is_array($args[1]) AND count($args[1]) > 0) {
				foreach($args[1] as $var) {
					$this->_data_requests[$var][$this->ec] = false;
				}
			}
			$this->children[$this->ec] = $method == '_text' ? mb_convert_encoding(htmlspecialchars($args[0], ENT_QUOTES, 'UTF-8'),'HTML-ENTITIES', 'UTF-8') : mb_convert_encoding($args[0],'HTML-ENTITIES', 'UTF-8');
			//$this->children[$this->ec] = $method == '_text' ? htmlspecialchars($args[0], ENT_QUOTES, 'UTF-8') : $args[0];
			return $this;
		}
		else {
			if($args[0]) $this->attr[$method] = $args[0];
			return $this;
		}
	}
	

	
	/**
	 * Iterate a DOM element over an array of data.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	private function ixml_iterate($var) {
		# figure out the loop type
		# @TODO add checks to this so that an error will be thrown if an invalid loop type is passed
		$this->loop_type = $var;
		$this->is_loop = true;
	}
	
	/**
	 * Iterate a DOM element over an array of data.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	private function ixml_source($var) {
		list($source, $as) = explode(' as ', $var);	
					
		$vars = $this->extract_vars($source);
		if($vars) {
			foreach($vars as $var) {
				$data_response = ($this->_data()->$var);
				try {
					$source = str_replace('{'.$var.'}', $data_response, $source);	
				} catch(Exception $e) {
					die('<h1>IXML Parse Error</h1><p>Trying to use the variable <code>'.$var.
					'</code> as a string, but it cannot be converted. Here are the contents of this variable:<pre>'.
					var_dump($data_response, true).'</pre></p><p>Source: <code>'.$source.'</code>');
				}
			}				
		}

		# add the variables to the new element source object
		$this->_data = new InterfaceHelper_Scope($this->_data());
		//var_dump($source);
		$this->_data()->source($source, $as);
	}
	
	
	/**
	 * Copy The Current DOM Element
	 *
	 * @param string $target 
	 * @return void
	 * @author David D. Boskovic
	 */
	private function _copy($target = 'after', $target_el = false) {
		
		# if no target element is passed than reference this element
		$target_el = is_object($target_el) ? $target_el : $this;
		
		switch($target) {
			case 'after' :
				# add the new element(s) directly after this element
			break;
			case 'before' :
				# add the new element(s) directly before this element
			break;
			case 'inside' :
				# duplicate the original contents of this element within the target element
			break;
			default :
				# throw error
			break;
		}
		
		# return the new element
		
	}
	
	private function parse_variables() {
		foreach($this->_data_requests as $data_request => $indexes) {
			$data_response = ($this->_data()->$data_request);
			foreach($indexes as $index => $val) {
				$this->children[$index] = str_replace('{'.$data_request.'}', $data_response, $this->children[$index]);
			}
		}
	}
	
	
	public function _loop_source(){
		$var = false;
		if(isset($this->attr['ixml:loop_source']))
			$var = $this->attr['ixml:loop_source'];
		elseif(isset($this->attr[':load']))
			$var = $this->attr[':load'];
		if(!$var) return false;
		
		list($source, $as) = explode(' as ', $var);	
		$vars = $this->extract_vars($source);
		if($vars) {
			foreach($vars as $var) {
				$data_response = ($this->_data()->$var);	
				$source = str_replace('{'.$var.'}', $data_response, $source);				
			}				
		}

		# add the variables to the new element source object
		$this->_data = new InterfaceHelper_Scope($this->_ ? $this->_->_data() : false);
		$this->_data()->source($source, $as);
	}
	
	public $_pattern = false;
	public function _add_pattern($p) {
		$this->_pattern = $p;
	}
	
	/**
	 * Generate a string of valid output XHTML.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function __toString() {
		try {
			return $this->_render();
		} catch(Exception $e) {
			$m = ($e->getMessage());
			$t = $e->getTraceAsString();
			
			echo $m . '<hr/><pre>' . $t .'</pre>';
			die;
		}
	}
	
	public function _render($ignore_pat = false) {
		if($this->_pattern && !$ignore_pat) {
			$p = $this->_pattern;
			// get the data for this element
			$cd = $this->_data();
			$d = $p->_data();
			
			// parse the attributes of this element and add the values to the current scope in the :pattern key.
			$attr_el = '<span ';
			foreach($this->attr as $attr => $val) {		
				$vars = $this->extract_vars($val);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($cd->$var);	
						$val = str_replace('{'.$var.'}', $data_response, $val);				
					}				
				}
				$d->data[$attr] = $val;
				$cd->data[':pattern'][$attr] = $val;
				$this->attr[$attr] = $val;
				$attr_el .= "$attr=\"$val\" ";
			}
			$tsource = $this->_render(1);

			$attr_el .= ' _attr_el="1"></span>';
			$d->data[':attr_el'] = $attr_el;
			$d->data[':source'] = $tsource;
			return (string) $p;
		}
		if(class_exists('Interface_Parser')) {
			if(Interface_Parser::$callback) {
				call_user_func(Interface_Parser::$callback,'output', $this, Interface_Parser::$callback_data);
			}
		}
		
		$html = '';
		# add iconic content
		if(isset($this->attr['class']) && strpos($this->attr['class'],'iconic') !== false) {
			$cc = $this->utility_get_class_html();
			unset($this->children['iconic']);
			$classes = explode(' ', $cc);
			foreach($classes as $class) {
				if(isset(self::$iconic[$class])) { $this->children['iconic'] = self::$iconic[$class]; break; }
			}
		}
		
		$this->_loop_source();
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
						$html .= $this->_parse_lang((string) $child);
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
					//var_dump($data_request, $indexes);
					foreach($indexes as $index => $val) {
						if(is_object($data_response))
							$data_response = describe($data_response);
						$parsed_children[$index] = str_replace('{'.$data_request.'}', $data_response, ($parsed_children[$index] ? $parsed_children[$index] : $this->children[$index]));
					}
				}	
			}	
			foreach($this->children as $key => $child) {
				if(!is_object($child)) {
					$html .= ''.(isset($parsed_children[$key]) ? $parsed_children[$key] : $child).'';
				} else {
					$html .= ' '.$this->_parse_lang((string) $child).' ';
				}
			}
		}

		if($el && $this->loop_type != 'self') {			
			$html .= $hc ? "</$el>" : "";
			$html .= !$hc && !isset(self::$quick_tags[$el]) ? "</$el>" : "";
		}
		return $html;
	}
	
	/**
	 * Parse language out of a string, this language can be anywhere in the final
	 * output after the tag has been converted to a string, so it is possible
	 * to generate a custom language tag like =person.wants.{:member.wants}=
	 */
	public function _parse_lang($text, $depth = 0) {
		
		# Get {literal}literal segments{/literal} and quarantine them
		$literals = explode('literal}', $text);
		if(isset($literals[1])) {
			// There are literal segments to parse through
			$out = array();
			foreach($literals as $index => $literal) {
				$len = strlen($literal);
				$last = $literal[$len - 1];
				
				// Process as literal
				if($index > 0 && $last == '/')
					$out[] = $literal;
				else
					$out[] = $this->_parse_lang($literal);
			}
			return implode('literal}', $out);
		}
		
		# Check for overrides for debugging
		if(isset($_GET['_debug_lang_off']))
			return $text;
		
		$mcount = preg_match_all('$=([\w\.\|\:\^]+)=$', $text, $matches);
					
		# check if any language processing is needed
		if($mcount > 0) {
			list($tags, $strings) = $matches;
			
			$sticky = array();
			$eq = '&#61;';
			
			foreach($strings as $string) {
				
				# Check if we should skip this tag
				
				if(substr($string, 0, 1) == '^') {
					$node = substr($string, 1);
					$text = str_replace("=$string=", "$eq$node$eq", $text);
					continue;
				}
				
				# Load the language path and start with the root node
				
				$filters = explode('|', $string);
				$key = array_shift($filters);
				
				$path = explode('.', $key);
				$node = e::$lang;
				$accumulate = '';
				$found = false;
				
				while($segment = array_shift($path)) {
					$found = true;
					$accumulate .= (strlen($accumulate) > 0 ? '.' : '') . $segment;
					
					# Handle normal children, these always take priority
					if(is_array($node) && isset($node[$segment])) {
						$node = $node[$segment];
						
						# Handle hard links
						
						if(is_string($node) && substr($node, 0, 1) == '~') {
							array_unshift($path, substr($node, 1));
							$node = e::$lang;
						}
						
						continue;
					}
					
					$template = false;
					$order = array(2, 1);
					foreach($order as $seq) {
					
						# Handle resolved_node.template.unresolved_segments
						
						if($seq == 1 && is_array($node) && isset($node['template'])) {
							array_unshift($path, $segment);
							$nkey = implode('.', $path);
							$value = $this->_parse_lang("=$nkey=", $depth + 1);
							
							$node = str_replace('@@@', $value, $node['template']);
							$template = true;
							break;
						}
						
						# Handle resolved_text.current_node.template.unresolved_segments
						
						if($seq == 2 && isset(e::$lang[$segment]['template'])) {
							if(is_string($node))
								$current = $node;
							else if(is_string($node['title']))
								$current = $node['title'];
							else
								$current = "$eq$accumulate$eq";
								
							$nkey = implode('.', $path);
							$value = $this->_parse_lang("=$nkey=", $depth + 1);
							
							$node = str_replace_first('@@@', $current, e::$lang[$segment]['template']);
							$node = str_replace('@@@', $value, $node);
							$template = true;
							break;
						}
					
					}
					
					# Handle no reasonable match
					
					if(!$template)
						$found = false;
					
					# Stop here
					
					break;
				}
				
				if($found == false)
					$node = "$eq$key$eq";
					
				else if(is_array($node)) {
					if(isset($node['title']) && !is_array($node['title']))
						$node = $node['title'];
					else
						$node = "=<code>$key.title</code>=";
				}
				
				# Get any variables out of the text
				
				$vars = $this->extract_vars($node);
				if($vars) {
					foreach($vars as $v) {
						$data_response = ($this->_data()->$v);
						if(is_object($data_response))
							$data_response = describe($data_response);
						$node = str_replace('{'.$v.'}', $data_response, $node);
					}
				}
				
				# Keep extracting until there's nothing left to extract, up to 10 layers
				
				$i = 0; do {
					$i++;
					$last = $node;
					$node = $this->_parse_lang($node);
				} while($node != $last && $i < 10);
				
				# Expand special words (like a*)
				
				$scount = preg_match_all('$\b(\w+\*)(.+)\b$', $node, $smatches);
				if($scount) {
					$words = $smatches[1];
					foreach($words as $index => $word) {
						$after = trim($smatches[2][$index]);
						$fchar = substr($after, 0, 4);
						$tchar = substr($after, 0, 2);
						$ochar = substr($after, 0, 1);
						$result = "[$word]";
						switch($word) {
							case 'a*':
							case 'an*':
								$chars = array($fchar, $tchar, $ochar);
								if(
									stripos('+hono hour', $fchar) ||
									stripos('+xr x- x 18', $tchar) ||
									stripos('+aeiou8', $ochar)
									)
									$result = 'an';
								else
									$result = 'a';
								break;
						}
						$node = str_replace_first($word, $result, $node);
					}
				}

				# Apply any filters (sticky first)
				
				$filters = array_merge($filters, $sticky);
				
				if(count($filters)) {
					foreach($filters as $raw) {
						$test = explode(':', $raw, 2);
						if(count($test) == 2) {
							$filter = $test[1];
							if($test[0] == 'sticky') {
								$sticky[$filter] = $filter;
							} else if($test[0] == 'clear') {
								unset($sticky[$filter]);
								continue;
							}
						} else
							$filter = $test[0];
						
						$this->_filter_text($filter, $node);
					}
				}
				
				# Inject the language
				
				$text = str_replace("=$string=", $node, $text);
			}
		}
		
		return $text;
	}
	
	public function _filter_text($filter, &$text) {
		switch($filter) {
			case 'lowercase':
				$text = strtolower($text);break;
			case 'uppercase':
				$text = strtoupper($text);break;
		}
	}
	
	public function is_positive($val) {
		if($val === true) return true;
		if(!$val || $val== 'false' || $val=='no' || $val == '0') return false;
		if($val || $val== 'true' || $val=='yes' || $val == '1') return true;
		return false;
	}
}