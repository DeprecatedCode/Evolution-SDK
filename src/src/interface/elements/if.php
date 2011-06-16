<?php

class InterfaceHelper_IXML_If extends InterfaceHelper {
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}

	public function __toString() {
		
		$v = $this->attr['var'];
		
		$vars = $this->extract_vars($v);
		if($vars) {
			foreach($vars as $var) {
				$data_response = ($this->_data()->$var);	
				$v = str_replace('{'.$var.'}', $data_response, $v);				
			}				
		}
		$val = $this->_data()->$v;
		if(isset($this->attr['agree'])) {
			if($this->attr['agree'] == 'yes') {
				if($this->is_positive($val))
					return parent::__toString();				
			}
			else {
				if(!$this->is_positive($val))
					return parent::__toString();				
			}
		}
		if(isset($this->attr['equals'])) {
			$equals_v = $this->attr['equals'];
			$vars = $this->extract_vars($equals_v);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$equals_v = str_replace('{'.$var.'}', $data_response, $equals_v);				
				}				
			}
			$equals_val = null;
			//var_dump($val);
			if($equals_val === null)
				$equals_val = $equals_v;
			if($equals_val == $val)
				return parent::__toString();
		}
        if(isset($this->attr['not'])) {
			$equals_v = $this->attr['not'];
			// Extract vars
			$vars = $this->extract_vars($equals_v);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$equals_v = str_replace('{'.$var.'}', $data_response, $equals_v);				
				}				
			}
			// End extract
			$equals_val = null;
			if($equals_val === null)
				$equals_val = $equals_v;
			if($equals_val != $val)
				return parent::__toString();
		}
		if(isset($this->attr['empty'])) {
			if(($this->attr['empty'] == 'false' && !empty($val)) || ($this->attr['empty'] == 'true' && empty($val)))
				return parent::__toString();
		}
		if(isset($this->attr['in'])){
			if(in_array($val,explode(',',$this->attr['in'])))
				return parent::__toString();
		}

		if(isset($this->attr['not_in'])){
			if(!in_array($val,explode(',',$this->attr['not_in'])))
				return parent::__toString();
		}
		
		if(isset($this->attr['gt']) AND isset($this->attr['lt'])) {
			if($val > $this->attr['gt'] && $val < $this->attr['lt'])
				return parent::__toString();
		}
		elseif(isset($this->attr['gt'])) {
			if($val > $this->attr['gt'])
				return parent::__toString();
		}
		
		elseif(isset($this->attr['gte'])) {
			if($val >= $this->attr['gte'])
				return parent::__toString();
		}
		elseif(isset($this->attr['lt'])) {
			if($val < $this->attr['lt'])
				return parent::__toString();
		}
		
		foreach($this->children as $key => $child) {
			if($child->fel == 'ixml:else') {
				$child->show_else = 1;
				return (string) $child;
			}
		}
		
		return '';
	}

}


class InterfaceHelper_IXML_Else extends InterfaceHelper {
	public $show_else = 0;
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}

	public function __toString() {
		if($this->show_else == 1) {
			$this->show_else = 0;
			return parent::__toString();
		}
		return '';
	}
	
	
}

