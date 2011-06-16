<?php

class InterfaceHelper_IXML_Interface extends InterfaceHelper {
	public $doctype = 'xhtml';
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}
	public function __toString() {
		switch($this->doctype) {
			case 'xhtml-strict' :
				$string = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
			break;
			case 'html5' :
				$string = "<!DOCTYPE html>\n";
			break;
			case 'xhtml' :
			case 'xhtml-transitional' :
			default :
				$string = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
			break;
		}
		return $string.parent::__toString();
	}
	
}