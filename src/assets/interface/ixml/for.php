<?php

class InterfaceHelper_IXML_For extends InterfaceHelper {
	public function __construct($el = false, $parent = false) {	
		parent::__construct($el, $parent);
		$this->el = false;
	
	}
	
	public function __toString() {
		//die(var_dump());	
	
		$from=$this->attr["from"] ? (float)$this->attr["from"] : 0;
		$to=$this->attr["to"] ? (float)$this->attr["to"] : 0;
		$inc=$this->attr["inc"] ? (float)$this->attr["inc"] : 1;
		$ret_val="";
		for($loop_cnt=$from;$loop_cnt<=$to;$loop_cnt+=$inc){
			$this->_data()->data[':forkey'] = $loop_cnt;
			$ret_val= $ret_val . parent::__toString();		
		}
		return $ret_val;
	}
}