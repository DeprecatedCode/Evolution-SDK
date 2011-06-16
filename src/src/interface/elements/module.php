<?php

class InterfaceHelper_IXML_Module extends InterfaceHelper {
	
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	
	public function __toString() {
		
		# load the new parsed structure
		$parse = new Interface_Parser('modules/'.$this->attr['name'].'.ixml');
		
		# load the variables into the scope.		
		foreach($this->children as $child) {
			switch($child->fel) {
				case 'ixml:var' :	
					$val = '';
					foreach($child->children as $ct)
						$val .= (string)$ct;
					$vars = $this->extract_vars($val);
					if($vars) {
						foreach($vars as $var) {
							$data_response = ($this->_data()->$var);	
							$val = str_replace('{'.$var.'}', $data_response, $val);				
						}				
					}					
					$parse->object->_data()->data['ixml:'.$child->attr['name']] = $val;
					$imports[$child->attr['name']] = 1;
				break;
			}
		}
		
		# run a pass through the parsed module children to get any configuration elements out and run them correctly
		foreach($parse->object->children as $child) {
			if($child->fel == 'ixml:configure_module') {
				foreach($child->children as $configuration) {
					switch($configuration->fel) {
						case 'ixml:vars' :
							foreach($configuration->children as $var) {
								$vv = $var->attr['name'];
								// check to see if the var is set or set it.
								if($this->is_positive($var->attr['required']) && !isset($imports[$vv]))
									die('The module `'.$this->fel.'` is missing a required variable:'.$vv);
								# if we haven't already imported a variable value try to get a default
								if(!isset($imports[$vv])) {
									$val = '';
									foreach($var->children as $ct)
										$val .= (string)$ct;
									if($val) {
										$vars = $this->extract_vars($val);
										if($vars) {
											foreach($vars as $var) {
												$data_response = ($this->_data()->$var);	
												$val = str_replace('{'.$var.'}', $data_response, $val);				
											}				
										}					
										$parse->object->_data()->data['ixml:'.$vv] = $val;									
									}									
								}
							}
						break;
					}
				}
			}
		}
		//var_dump($parse->object->_data->data);
		return (string) $parse->object;
	}
}