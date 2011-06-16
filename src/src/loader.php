<?php

class e_Loader {

	protected $_object_prefix;
	protected $_dir;
	protected $_rel_dir;
	protected $_default_file = '_default.php';

	public function __construct($dir = false) {
		$this->_object_prefix = $this->_object_prefix ? $this->_object_prefix : substr(get_class($this),0, -6);
		if($dir == false && (!$this->_dir && !$this->_rel_dir)) die(get_class($this).' not initialized with a directory configuration.');
		if($this->_rel_dir AND $dir == false) $this->_dir = ROOT.$this->_rel_dir;
		if($dir == true) $this->_dir = $dir;
		$this->init($dir);
		$this->_dir = rtrim($this->_dir,'/');
	}

	public function init($dir) {
		
	}
	
	public function get($file) {
		return $this->__get($file);
	}

	public function __get($file) {
		if(file_exists("$this->_dir/$file.php")) {
			include_once("$this->_dir/$file.php");
			$cn = $this->_object_prefix.$file;
			return new $cn;
		}
		elseif($this->_default_file == '@self' && file_exists("$this->_dir/$file/$file.php")) {
			include_once("$this->_dir/$file/$file.php");
			$cn = $this->_object_prefix.$file;
			return new $cn;
		}
		elseif(file_exists("$this->_dir/$file/$this->_default_file")) {
			include_once("$this->_dir/$file/$this->_default_file");
			$cn = $this->_object_prefix.$file;
			return new $cn;
		}
		else {
			debug_print_backtrace();
			die(get_class($this).' could not load '.$file);
		}
	}

	public function __call($file, $args) {
		if(file_exists("$this->_dir/$file.php")) {
			include_once("$this->_dir/$file.php");
			$cn = $this->_object_prefix.$file;
			return new $cn(@$args[0],@$args[1]);
		}
		elseif($this->_default_file == '@self' && file_exists("$this->_dir/$file/$file.php")) {
			include_once("$this->_dir/$file/$file.php");
			$cn = $this->_object_prefix.$file;
			return new $cn(@$args[0],@$args[1]);
		}
		elseif(file_exists("$this->_dir/$file/$this->_default_file")) {
			include_once("$this->_dir/$file/$this->_default_file");
			$cn = $this->_object_prefix.$file;
			return new $cn(@$args[0],@$args[1]);
		}
		else {
			die(get_class($this).' could not load '.$file);
		}
	}
}