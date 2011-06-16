<?php

class InterfaceHelper_IXML_Select extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = 'select';
	}
	
	public function __toString() {
		$sel_val = isset($this->attr['default']) ? $this->attr['default'] : false;
		if($this->attr['selected']) {	
			$val = $this->attr['selected'];
			$vars = $this->extract_vars($val);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$val = str_replace('{'.$var.'}', $data_response, $val);				
				}				
			}
			
			$sel_val = $val ? $val : $sel_val;
		}
		foreach($this->children as $key => $child) {
			if(isset($child->attr['value']) && $child->attr['value'] == $sel_val) {
				$child->_attr(array('selected' =>'selected'));
				break;
			}
		}
		
		return parent::__toString();
	}
}