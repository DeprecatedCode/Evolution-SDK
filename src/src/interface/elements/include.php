<?php

class InterfaceHelper_IXML_Include extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function _attr($array) {
		parent::_attr($array);
		if(isset($array['interface'])) {			
			$parse = new Interface_Parser($array['interface'].'.ixml', $this);
		}
	}
}