<?php

class InterfaceHelper_IXML_Extend extends InterfaceHelper {
	
	public $_extending_done = false;
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function __toString() {
		
		$array = $this->attr;
		if(!$this->_extending_done) {
			$this->_extending_content = clone $this;
			foreach($this->_extending_content->children as $child) {
				if(is_object($child)) {
					$child->_ = $this->_extending_content;
				}
			}
			$this->_extending_content->_extending_done = true;
			$this->children = array();
			$this->ec = 0;
			
			if(isset($array['interface'])) {
				$v = $array['interface'];
				$vars = $this->extract_vars($v);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$v = str_replace('{'.$var.'}', $data_response, $v);				
					}				
				}
				$dir = e::$url->portal ? ROOT_PORTALS.'/'.e::$url->portal.'/interface/' : '';
				$parse = new Interface_Parser($dir.$v.'.ixml', $this);
			}
		}
		
		return parent::__toString();
	}
}
class InterfaceHelper_IXML_ExtendHere extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
		$ext = $this->_extending_content();
		if($ext) $this->_hardwire($ext);
	}
	
}