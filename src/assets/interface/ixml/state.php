<?php

class InterfaceHelper_IXML_State extends InterfaceHelper {
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}

	public function __toString() {
		$type = $this->attr['type'];
		$as = $this->_data()->source_as;
		$response = $this->_data()->$as;
		if($type == 'empty') {
			//var_dump(parent::__toString());
			if(!$response) return parent::__toString();
			elseif(is_object($response) && method_exists($response, 'count')) {
				if($response->count('*') == 0) return parent::__toString();
			}
		} else {
			if(is_object($response) && method_exists($response, 'count')) {
				if($response->count(1) > 0) return parent::__toString();
			}
			elseif($response) return parent::__toString();
		}
		return '';
	}
	
	
}

