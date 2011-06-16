<?php

/**
 * Interface Parser
 *
 * @package default
 * @author David D. Boskovic
 */
class Interface_Parser {
	
	/**
	 * Current parsing position.
	 *
	 * @var integer
	 */
	public $pointer = 0;
	
	/**
	 * The HTML string being parsed.
	 *
	 * @var string
	 */
	public $html = '';
	public static $callback = false;
	public static $callback_data = false;
	
	
	/**
	 * The file being parsed.
	 *
	 * @var string
	 */
	public $file = '';
	
	/**
	 * The current line number.
	 *
	 * @var string
	 */
	public $line = 0;
	
	/**
	 * The HTML helper object.
	 *
	 * @var MiniHelper_HTML object
	 */
	public $object;
	
	public static $cache = array();
	
	public static $parse_time = 0;
	
	function register_callback($callback, $data = false) {
		self::$callback = $callback;
		self::$callback_data = $data;
	}
	function unregister_callback() {
		self::$callback = false;
		self::$callback_data = false;
	}

	
	/**
	 * Initialize the parser with a file or string.
	 *
	 * @param string $string 
	 * @author David D. Boskovic
	 */
	function __construct($string, $html_object = false, $from_file = true) {
		$t = microtime(true);
		if(!$from_file) {
			$this->html = $string;
		} else if(isset(self::$cache[$string])) {
			$this->html = self::$cache[$string];

			$this->file = self::$cache[$string . '@file'];
		} else {
			$priority = array(SUPER_ROOT_THEME.'/', '', SUPER_ROOT_INTERFACE.'/', ROOT_INTERFACE.'/');
			
			foreach($priority as $location) {
				if(file_exists($location . $string)) {
					$this->file = $location . $string;
					$this->html = file_get_contents($this->file);
					break;
				}

			}
			
			if($this->file == '') {
				e::fault(100, 'ixml_interface_not_exist', array('interface' => $string));
			}

			self::$cache[$string] = $this->html;
			self::$cache[$string . '@file'] = $this->file;		
		}
		
		$this->object = $html_object;
		$this->parse();
		self::$parse_time += (microtime(true)-$t)*1000;
	}
	
	/**
	 * Parse through the HTML and create an HTML helper module from it.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	function parse() {
		# create an instance of the html helper and copy it to the current element pointer
		if(!$this->object) $this->object = new InterfaceHelper;
		$current_element = $this->object;
		
		# establish a dynamic array of tags as they get opened and closed
		$open_tags = array();
		$open_tag_id = 0;
		$force_html = false;
		
		# Parse through the HTML tag by tag		
		while($tag = $this->get_tag($force_html)) {
			if($tag[0] == 'start' && ($tag[1] == 'script' || $tag[1] == 'style')) $force_html = $tag[1];
			else $force_html = false;
			if($tag[0] == 'comment') $current_element->_html($tag[1]);
			
			# assign the array items to variable  names for more beautiful access
			list($tag_type, $tag_name, $tag_attributes) = $tag;
			
			# handle a parsing error
			if($tag_type == 'end' && $tag_name !== $open_tags[$open_tag_id]) {
				display_file_at_line('IXML Parse Error', $this->file, $this->line);
				die('I was expecting the end tag <code>&lt;/'.$open_tags[$open_tag_id].'&gt;</code>, instead I got <code>&lt;/'.$tag_name.'&gt;</code>');
			}
			
			# take the right actions depending on the tag type
			switch($tag_type) {
				case 'start' :
					++$open_tag_id;
					$current_element = $current_element->$tag_name->_attr($tag_attributes);
					$open_tags[$open_tag_id] = $tag_name;
				break;
				case 'start-end' :
					$current_element->$tag_name->_attr($tag_attributes);
				break;
				case 'end' :
					unset($open_tags[$open_tag_id]);
					--$open_tag_id;
					$current_element = $current_element->_;
				break;
				case 'text' :
					//var_dump($tag_attributes);
					$current_element->_html($tag_name, $tag_attributes);
				break;
			}
		}
	}
	
	/**
	 * Get the next tag from the current pointer, whether that be a starting or ending tag.
	 *
	 * @return array | boolean
	 * @author David D. Boskovic
	 */
	function get_tag($force_html) {
		
		# get the next available "<" from the pointer
		$s = $force_html ? strpos($this->html, '</'.$force_html.'>', $this->pointer) : strpos($this->html, '<', $this->pointer);

		# check for comment
		$comment = strpos($this->html,'!--',$s)-$s == 1 ? true : false;
		
		# if we couldn't find a new tag, just return false
		if($s === false) return false;
		
		# if this node is a text node, return a text node.
		$text_node = trim(substr($this->html, $this->pointer, $s-$this->pointer));		
		if(strlen($text_node) != 0) {
			$text_vars = $this->extract_vars($text_node, $force_html ? true : false);
			//var_dump($force_html ? true : false, $text_vars);
			$this->pointer = $s;
			return array('text', $text_node, $text_vars);
		}
		
		# get the next closing ">" after $s
		# @TODO make this capable of ignoring inline JS comparison operators
		$e = $comment ? strpos($this->html, '-->',$s)+3 : strpos($this->html, '>',$s)+1;
		
		# set the pointer to the position immediately after the closing symbol of the last tag
		$this->pointer = $e;
		
		# extract the html tag from the HTML
		$tag = substr($this->html, $s, $e-$s);
		//if($comment) var_dump($tag);

		if(!$comment):
			# extract the tag name
			$tag_name = strpos($tag, ' ') !== false ? trim(substr($tag, 1, strpos($tag, ' ')), '<>/ ') : trim($tag, '<>/ ');

			# identify the type of tag we just opened. start, end, or start-end
			$tag_type = strpos($tag, '/>') !== false ? 'start-end' : (strpos($tag, '</') !== false ? 'end' : 'start');
		else:
			$tag_type = 'comment';
			$tag_name = $tag;
		endif;
		
		$this->line = substr_count(substr($this->html, 0, $this->pointer), "\n") + 1;
		
		# return the array of tag data
		return array($tag_type, $tag_name, $comment ? false : $this->get_attributes($tag));		
	}
	
	/**
	 * Extract any variables from a string.
	 *
	 * @author David D. Boskovic
	 */
	
	private function extract_vars($content, $special = false) {
		
		// parse out the variables
		preg_match_all(
			$special ? "/{(\%[\w:|.\,\(\)\[\]\/\-\% ]+?)}/" : "/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/", //regex
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
	 * Get the attributes out of an html tag such as: <tag attr="whatever">
	 *
	 * @param string $tag 
	 * @return array
	 * @author David D. Boskovic
	 */
	function get_attributes($tag){

		# match the attributes using regex
		preg_match_all('/(?:([^\s]*[\:]*[^\s]*))="(?:([^"]*))"/', $tag, $matches, PREG_SET_ORDER);
		
		# loop through the matches and create a result array in the right format
		$attrs = array();
		foreach($matches as $match) {
			$attrs[$match[1]] = $match[2];
		}
		
		# return the array of tag attributes, empty array if none
		return $attrs;
	}
}
