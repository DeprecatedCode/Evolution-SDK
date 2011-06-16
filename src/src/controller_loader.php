<?php

class e_Controller_Loader extends e_Loader {
	protected $_object_prefix = "Controller_";
	protected $_default_file = '@self';
	
	
	public function __get($file) {		
		
		$class = $this->_object_prefix.$file;
		if(class_exists($class, true)) {
			$att = new $class(true);
			return $att;
		}
		die(get_class($this).' could not load '.$file);
	}
}