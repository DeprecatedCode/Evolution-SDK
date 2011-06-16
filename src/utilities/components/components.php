<?php
include('component_object.php');
include('model_object.php');

/**
 * Special utilities for fetching and adjusting components
 *
 * @package evolution
 * @author David Boskovic
 **/
class e_utility_components
{
	public function list_components() {
		$a = array();
		foreach((array) e::$configure->components as $com => $etc) {
			$a[] = new e_utility_components_object($com);
		}
		return $a;
	}
	
	
	public function component($com) {
		return new e_utility_components_object($com);
	}
	
	public function create_new($slug, $configure) {
		
	}
} // END class e_utility_components