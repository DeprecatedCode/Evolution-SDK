<?php

class e_Action_Loader extends e_Loader {
	protected $_object_prefix = "Action_";
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