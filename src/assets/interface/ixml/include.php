<?php

class InterfaceHelper_IXML_Include extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function _attr($array) {
		parent::_attr($array);
		if(isset($array['interface'])) {
			$v = $array['interface'];
			$vars = $this->extract_vars($v);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$v = str_replace('{'.$var.'}', $data_response, $v);				
				}				
			}
			$if =1;
			if(isset($array['if'])) {
				$if = $array['if'];
				$vars = $this->extract_vars($if);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$if = str_replace('{'.$var.'}', $data_response, $if);				
					}				
				}
			}
			$dir = e::$url->portal ? ROOT_PORTALS.'/'.e::$url->portal.'/interface/' : '';
			
			if($this->is_positive($if))
				$parse = new Interface_Parser($dir.$v.'.ixml', $this);
		}
	}
}