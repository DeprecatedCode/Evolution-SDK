<?php

class InterfaceHelper_IXML_Dropdown extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function __toString() {
		if(!$this->attr[':source']) e::$error->fault(100,'ixml_dropdown_no_source');
		
		foreach($this->attr as $attr => $val) {	
			if($this->attr['selected']) {	
				$vars = $this->extract_vars($val);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$val = str_replace('{'.$var.'}', $data_response, $val);				
					}				
				}
				$this->attr[$attr] = $val;
			}
		}
		$source = $this->_data()->get($this->attr[':source']);
		$dropdown = $this->select->_attr($this->attr);
		if($this->attr['selected']) $sel = $this->attr['selected'];
		foreach($source as $val => $label) {
			$dropdown->option->value($val)->selected(isset($sel) && $sel == $val ? 'selected' : false)->_text($label);
		}
		return (string) $dropdown;
	}
}