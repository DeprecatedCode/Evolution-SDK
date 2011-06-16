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
	
		
		/**
		 * Make sure we have the basic attributes we need.
		 */
		if(!isset($this->attr['name']))
			e::fault(50, 'ixml_template_no_name', $this->attr);
		
		
		/**
		 * Load up our template pattern.
		 */	
		$dir = ROOT_PORTALS.'/'.e::$url->portal.'/';
		$parse = new Interface_Parser($dir.'interface/_templates/'.$this->attr['name'].'.tpl');
		$template = $parse->object;
		
		$html = $template->_children(':html', 1);
		$el_patt = $template->_children(':patterns', 1);
		$epatterns = $el_patt->_children(':pattern');
		$patterns = array();
		foreach($epatterns as $epattern) {
			if(!isset($epattern->attr['for'])) continue;
			// give each pattern a fresh scope
			$epattern->_data = new InterfaceHelper_Scope;
			$patterns[$epattern->attr['for']] = $epattern;
		}	
		$html->_data = new InterfaceHelper_Scope;
		
		/**
		 * Iterate through this element's children and embed them into the template.
		 */
		$source = '';
		foreach($this->children as $key => $child) {
			if(is_string($child)) continue;
			$args = $child->attr;
			if(isset($patterns[$child->fel])) {
				$child->el = false;
				$tsource = (string) $child;
				$p = $patterns[$child->fel];
				$d = $p->_data();
				foreach($child->attr as $attr => $val) {		
					$vars = $this->extract_vars($val);
					if($vars) {
						foreach($vars as $var) {
							$data_response = ($child->_data()->$var);	
							$val = str_replace('{'.$var.'}', $data_response, $val);				
						}				
					}
					$d->data[$attr] = $val;
				}
				$d->data[':source'] = $tsource;
				$source .= (string) $p;
			}
			else $source .= (string) $child;
		}

		$hd = $html->_data();
		$hd->data[':source'] = $source;
		
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

