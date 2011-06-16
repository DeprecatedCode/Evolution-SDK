<?php

class e_Filter_Loader {
	public function get(&$source, $filter, $args = array()) {
		$f = '_filter_'.$filter;
		if(function_exists($f))
			$f($source, $args);
		//echo 'test';
		return $source;
	}
	public function __call($method, $args) {
		return $this->get($method,$args);
	}
}