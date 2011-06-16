<?php

/**
 * Load configuration files from yaml, xml, or cache depending on configuration and 
 *
 * @package default
 * @author David Boskovic
 */
class e_Documentation extends e_Loader {
	

	protected function _get_configuration($library) {
		return e::helper('markdown')->transform(@file_get_contents("$this->_dir/$library.md"));
	}

	protected function _get_configuration_time($library) {
		return @filemtime("$this->_dir/$library.md");
	}
	
	public function __get($file) {
		$key = md5($this->_dir);
		# load the environments
		if(cache::check($key, $file)) {
			$result = cache::get($key, $file);
		}
		if(!$result || ($this->_get_configuration_time($file) > cache::timestamp($key, $file))) {
			$result = $this->_get_configuration($file);
			cache::store($key, $file, $result, 'base64');
		}
		return $result;
		
	}
}