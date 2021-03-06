<?php

/**
 * Manages the evolution php variable cache.
 *
 * @package mini
 * @author David D. Boskovic
 */
class e_cache {
	public $cache = array();
	public $cache_update_time = array();
	public $check_record = array();
	private $cache_header = "<?php \n# this document has been automatically generated by the (mini) framework caching engine. \n# DO NOT MODIFY!!!!!!!!!!!!!!!!!!\n\n";
	
	/**
	 * Check the library to see if there's a cached value for the requested variable.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function check($library, $key) {
		$mkey = md5($key);
		if(isset($this->cache[$library][$key])) {
			return true;
		}
		elseif(file_exists(ROOT_LIBRARY."/cache/$library/$mkey.cache")) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function timestamp($library, $key) {
		$mkey = md5($key);
		if(!$this->check($library, $key))
			return false;
		else
			return filemtime(ROOT_LIBRARY."/cache/$library/$mkey.cache");
	}
	
	/**
	 * Get the value of a cached variable. Returns NULL if the variable is not cached.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return mixed
	 * @author David D. Boskovic
	 */
	public function get($library, $key) {
		if($this->check($library, $key)) {
			if($this->_is_loaded($library, $key))
				return $this->cache[$library][$key];
			else
				return $this->_load($library, $key);
		}
		else
			return NULL;		
	}
	
	public function _is_loaded($library, $key) {
		return isset($this->cache[$library][$key]);
	}
	
	public function _load($library, $key) {
		$mkey = md5($key);
		if($this->check($library, $key)) {
			$data = file_get_contents(ROOT_LIBRARY."/cache/$library/$mkey.cache");
			$data = unserialize(base64_decode($data));
			$this->cache[$library][$key] = $data;
			return $this->cache[$library][$key];
		}
		else return NULL;
	}
	
	/**
	 * Save a value to memory and the cache file.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @param string $value 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function store($library, $key, $value, $encrypt = false) {
		# make sure the current library values are loaded
		$this->check($library, $key);
		
		switch($encrypt) {
			default:
				# get base64string
				$save_value = wordwrap(base64_encode(serialize($value)), 120, "\n", true);
			break;
		}
		$this->cache[$library][$key] = $value;
		return $this->write($library, $key, $save_value);
	}
	
	/**
	 * Delete a value from memory and the cache file.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @param string $value 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public function delete($library, $key) {
		
		# make sure the current library values are loaded
		if(!$this->check($library, $key)) return true;
		$mkey = md5($key);
		$file = ROOT_LIBRARY."/cache/$library/$mkey.cache";
		unlink($file);
		return true;
	}
	
	/**
	 * Handle the actual writing of the cache file.
	 *
	 * @param string $library 
	 * @return void
	 * @author David D. Boskovic
	 */
	private function write($library, $key, $string) {
		$mkey = md5($key);
		
		# get the string to save to the file
		if(!is_writable(ROOT_LIBRARY."/cache/")) {
			return false;
		} else {
			if(!is_dir(ROOT_LIBRARY."/cache/$library")) {
				mkdir(ROOT_LIBRARY."/cache/$library");
			}
			$file = ROOT_LIBRARY."/cache/$library/$mkey.cache";
			$fh = fopen($file, 'w') or die("can't open file");
			fwrite($fh, $string);
			fclose($fh);
			return true;
		}
	}
	
	private function _decrypt($library, $key) {
		$working_copy = $this->cache[$library][$key];
		$fv = strpos($working_copy, '|');
		$conf = substr($working_copy, 0, $fv);
		$working_copy = substr($working_copy, $fv);
		$r = explode(':', $conf);
		
		switch($r[1]) {
			case 'base64' :
				return unserialize(base64_decode($working_copy));
			break;
		}
	}
	
	private function _timestamp_segment($library, $key) {
		if($this->check($library,$key))
			$this->cache_update_time[$library][$key] = microtime(true);
		$string = '';
		if(is_array($this->cache_update_time))
			foreach($this->cache_update_time[$library] as $k => $time) {			
				$human_readable = date('M d, Y H:i:s e (\G\M\T P)',$time);
				$string .= "# last update $human_readable\n".'$_timestamp["'.$k.'"] = '."$time;\n\n";
			}
		return "# ---------------------------------------------------\n\n".$string;
	}
	
}