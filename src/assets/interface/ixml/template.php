<?php

class InterfaceHelper_IXML_Template extends InterfaceHelper {
	
	public function __construct($el = false, $parent = false) {
		parent::__construct($el, $parent);
		$this->el = false;
	}

	/**
	 * When this element is turned into html, we do all the processing so we can produce a beautiful form.
	 *
	 * @return string
	 * @author David D. Boskovic
	 */
	public function __toString() {
	
		$this->_loop_source();
		
		/**
		 * Make sure we have the basic attributes we need.
		 */
		if(!isset($this->attr['name']))
			e::fault(50, 'ixml_template_no_name', $this->attr);
		
		
		/**
		 * Load up our template pattern.
		 */	
		$theme = array('template' => @e::$session->data['theme_name']);
		if(empty($theme['template'])) $theme=cache::get('settings',"template");
		if(empty($theme["template"])) $theme["template"]=DEFAULT_THEME;
		
		if(strpos($this->attr['name'], '://') > 0) {
			$theme["template"] = substr($this->attr['name'],0,strpos($this->attr['name'], '://'));
			$this->attr['name'] = substr($this->attr['name'],strpos($this->attr['name'], '://')+3);
		}
		$parse = new Interface_Parser('../themes/'.$theme["template"].'/'.$this->attr['name'].'.tpl');
		$template = $parse->object;
		
		$html = $template->_children(':html', 1);
		$el_patt = $template->_children(':patterns', 1);
		$epatterns = $el_patt ? $el_patt->_children(':pattern') : array();
		$patterns = array();
		foreach($epatterns as $epattern) {
			if(!isset($epattern->attr['for'])) continue;
			// give each pattern a fresh scope
			$epattern->_data = new InterfaceHelper_Scope;
			$patterns[$epattern->attr['for']] = $epattern;
			
		}	
			$html->_data = new InterfaceHelper_Scope('div');
		
		
		/**
		 * Iterate through this element's children and embed them into the template.
		 */
		$source = '';
		$ups = array();
		foreach($this->children as $key => $child)
			if(is_object($child)) 
				if(isset($patterns[$child->fel]))
					$ups[$child->fel] = $child->fel;
					
		$patterns_rendered = array();
		
		// iterate through declared patterns
		foreach($patterns as $for => $p) {
			
			// should we output to source
			$to_source = isset($p->attr['to_source']) && $p->attr['to_source'] == '0' ? false : true;
			
			// pattern data element
			$d = $p->_data();
			
			$d->data[':implemented_patterns'] = $ups;
			
			// find any instances of this element and loop through
			$instances = $this->_find($for,0);
			if($instances)
				foreach($instances as $el) {
					// shut off printing of this pattern element
					$el->el = false;					
					$el->_add_pattern($p);
				}
		}
		foreach($this->children as $key => $child) {
			if(is_string($child)) {
				$val = $child;	
				$vars = $this->extract_vars($child);
				if($vars) {
					foreach($vars as $var) {
						$data_response = ($this->_data()->$var);	
						$val = str_replace('{'.$var.'}', $data_response, $val);				
					}				
				}
				$source .= $val;
			}
			else {
				$args = $child->attr;
				if(isset($patterns[$child->fel])) {
					$p = $patterns[$child->fel];
					$to_source = isset($p->attr['to_source']) && $p->attr['to_source'] == '0' ? false : true;
					$patterns_rendered[$child->fel] .= (string) $child;
					if($to_source) $source .= (string) $child;
				}
				else $source .= (string) $child;
				
			}
		}
		$hd = $html->_data();
		$hd->data[':source'] = $source;
		$hd->data[':patterns'] = $patterns_rendered;
		
		foreach($this->attr as $attr => $val) {		
			$vars = $this->extract_vars($val);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$val = str_replace('{'.$var.'}', $data_response, $val);				
				}				
			}
			$hd->data[$attr] = $val;
		}
		
		return (string) $html;	

	}
	
	
}

