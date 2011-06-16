<?php

class InterfaceHelper_IXML_Snippet extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function __toString() {
		$dir = e::$url->portal ? ROOT_PORTALS.'/'.e::$url->portal.'/interface/' : '';
		$parse = new Interface_Parser($dir.'_snippets/'.$this->attr['name'].'.ixml');
		$string = parent::__toString();
		foreach($this->attr as $attr => $val) {		
			$vars = $this->extract_vars($val);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$val = str_replace('{'.$var.'}', $data_response, $val);				
				}				
			}
			$parse->object->_data()->data[':ixml'][$attr] = $val;
		}
		$parse->object->_data()->data[':ixml']['source'] = $string;
		return (string) $parse->object;
	}
}