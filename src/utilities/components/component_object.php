<?php

class e_utility_components_object {
	
	public $configure;
	public $name;
	
	public function __Construct($slug) {
		if(isset(e::$configure->components[$slug])) {
			$this->configure = new e_Configure(ROOT_COMPONENTS."/$slug/configure");
			$this->name = $this->configure->information['name'];
			$this->description = $this->configure->information['description'];
			$this->license = $this->configure->information['license'];
			$this->slug = $slug;
		}
	}
	
	public function models() {
		$models = $this->configure->models;
	}
	
	public function lists() {
		
	}
	
	public function __Get($var) {
		return $var;
	}
	public function __toString() {
		return '[utility:component:object->NULL]';
	}
}