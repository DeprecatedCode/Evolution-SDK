<?php

class InterfaceHelper_IXML_Switch extends InterfaceHelper {
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}

	public function __toString() {
		$v = $this->attr['var'];
		$val = $v == '_logged_in' ? (e::$session->member->id ? 1 : 0) : $this->_data()->$v;

		foreach($this->children as $key => $child) {
			if($child->fel == 'ixml:case') {
				
				if(isset($child->attr['equals'])) {
					$v = $child->attr['equals'];
					$vars = $this->extract_vars($v);
					if($vars) {
						foreach($vars as $var) {
							$data_response = ($this->_data()->$var);	
							$v = str_replace('{'.$var.'}', $data_response, $v);				
						}				
					}
				
					if($v == $val)
						return (string) $child;
				}
				if(isset($child->attr['default'])) {					
					return (string) $child;
				}
				
				if(isset($child->attr['agree'])) {
					if($child->attr['agree'] == 'yes') {
						if($this->is_positive($val))
							return (string) $child;				
					}
					else {
						if(!$this->is_positive($val))
							return (string) $child;				
					}
				}
			}
		}
		return '';
	}
	
	
}

