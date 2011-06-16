<?php

class InterfaceHelper_IXML_XHTML extends InterfaceHelper {
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = 'html';
	}
}