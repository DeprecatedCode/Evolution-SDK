<?php

class InterfaceHelper_IXML_Configure extends InterfaceHelper {
	
	public $doctype;
	public $on;
	public $vars;
	
	public function __toString() {
		return '';
	}
	
	public function _attr($a) {
		
		switch($this->on) {
			
			case 'ixml:url' :
			break;
			case 'ixml:authenticate' :
				if(!isset($a['with']))
					e::fault(50, 'ixml_no_authenticate_with_attr');
				# run the authenticate method
				$this->_data()->get($a['with']);
			break;
			case 'ixml:var' :
				if(substr($a['source'],0,3) == 'url') {
					$handle = trim(substr($a['source'],3), "()");
					$val = url::$segments[$handle] ? url::$segments[$handle] : $a['default'];
					$this->vars[$a['name']] = $val;
					$this->_data()->data[$a['name']] = $val;
				}
				elseif(substr($a['source'],0,3) == 'get') {
					$key = trim(substr($a['source'],3), "()");
					$val = $_GET[$key];
					$this->vars[$a['name']] = $_GET[$key];
					
					if($a['encoding'] == 'base64')
						$val = base64_decode(($val));
					if($a['format'] == 'serialized')
						$val = unserialize($val);
						
					e::variable($a['name'], $val);
					$this->_data()->data[$a['name']] = $val;
				}
				elseif(substr($a['source'],0,7) == 'session') {
					$key = trim(substr($a['source'],7), "()");
					$val = e::$session->data($key);
					$this->vars[$a['name']] = e::$session->data($key);
					
					if($a['encoding'] == 'base64')
						$val = base64_decode(($val));
					if($a['format'] == 'serialized')
						$val = unserialize($val);
						
					e::variable($a['name'], $val);
					$this->_data()->data[$a['name']] = $val;
				}
				
				if($a['required'] == 'yes' || $a['required'] == '1') {
					//Josh: Added a default value
					
					if(empty($this->vars[$a['name']])){						
						if(!(isset($a["default"]) && strlen($a["default"])>0)){
							die('IXML Requires The Variable: '.$a['source']);
						}else{
							$this->_data()->data[$a['name']] = $a['default'];
						}
					}
				}
			break;
			case 'ixml:doctype' :
				$this->_->doctype = $a['type'];
			break;
			case 'ixml:protocol' :
				
				if($a['type'] == 'https') {
					if($_SERVER['SERVER_PORT'] != '443' && substr($_SERVER['SERVER_NAME'],-3) != 'dev' && $_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != 'phoenix' && $_SERVER['SERVER_NAME'] != '192.168.1.101')
						{
						//e::redirect('https://secure.globalfast.org'.$_SERVER['REQUEST_URI']);
						//e::redirect('javascript:alert("This page should be accessed through SSL only.")');
					}
				} else {			
					//echo 'test';		
					//var_dump($_SERVER);
					if($_SERVER['SERVER_PORT'] == '443' && substr($_SERVER['SERVER_NAME'],-3) != 'dev' && $_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != 'phoenix' && $_SERVER['SERVER_NAME'] != '192.168.1.101'){
						//e::redirect('http://phoenix.globalfast.org'.$_SERVER['REQUEST_URI']);

					}elseif(strpos($_SERVER['HTTP_HOST'],'globalfast.org') === false && substr($_SERVER['SERVER_NAME'],-3) != 'dev' && $_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != 'phoenix' && $_SERVER['SERVER_NAME'] != '192.168.1.101'){
						//e::redirect('http://phoenix.globalfast.org'.$_SERVER['REQUEST_URI']);
					}
				}
			break;
			default:
			break;
		}
		return $this;
	}
	
	public function __get($var) {
		$this->on = $var;
		return $this;
	}
	public function __call($method, $args) {
		return $this;
	}
	
}

