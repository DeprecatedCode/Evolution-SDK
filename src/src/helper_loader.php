<?php

class e_Helper_Loader extends e_Loader {
	protected $_object_prefix = "Helper_";
	protected $_default_file = '@self';
	
	public function __call($method, $args) {
		$helper = $this->get($method, $args);
		if(method_exists($helper,'run_'.$method))
			return call_user_func_array(array($helper,'run_'.$method), $args);
	}
}