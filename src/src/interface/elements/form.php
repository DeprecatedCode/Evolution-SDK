<?php

class InterfaceHelper_IXML_Form extends InterfaceHelper {
	
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
		
		# replace any variables passed in the attributes
		foreach($this->attr as &$val) {		
			$vars = $this->extract_vars($val);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);	
					$val = str_replace('{'.$var.'}', $data_response, $val);				
				}				
			}
		}
		
		/**
		 * Make sure we have the basic attributes we need.
		 */
		if(!isset($this->attr['model']))
			e::$error->fault(50, 'ixml_form_no_model', $this->attr);
		if(!isset($this->attr['id']))
			e::$error->fault(50, 'ixml_form_no_id', $this->attr);

		/**
		 * Parse out the app and model from the map passed.
		 */
		$app = substr($this->attr['model'], 0,strpos($this->attr['model'],'.'));
		$model = substr($this->attr['model'], strpos($this->attr['model'],'.')+1);
		
		$model = e::app($app)->$model($this->attr['id']);

		/**
		 * Get the list of fields we want to show if we've passed the attribute fields="field,field"
		 */
		if($this->attr['fields']) {
			$show_fields = explode(',', $this->attr['fields']);
			foreach($show_fields as &$field)
				$field = trim($field);
			$show_fields = array_flip($show_fields);
		}
		
		/**
		 * Setup some empty arrays.
		 */
		$patterns = array();
		$fieldsets = array();
		$string = array();
		
		/**
		 * Setup a default form pattern in case no patterns have been specified.
		 */
		$def = $this->div->class('form_item');
		$def->label->_html('{:ixml.label}',array(':ixml.label'));
		$def->div->_html('{:ixml.input}',array(':ixml.input'));
		$patterns['*'] = $def;
		
		# setup the form to return
		$form = $this->form->action($this->attr['action'])->method(isset($this->attr['method']) ? $this->attr['method'] : 'post')->enctype(isset($this->attr['enctype']) ? $this->attr['enctype'] : 'multipart/form-data');
		foreach($this->children as $key => $child) {
			if($child->fel == 'ixml:pattern') {
				$for = $child->attr['for'];
				$patterns[$for] = $child;
			}
			if($child->fel == 'ixml:fieldset') {
				$fieldsets[] = $child->attr;
			}
		}
		$fields = $model->_form_config();
		# loop through the fields we have to edit
		foreach($fields as $field => $config) {
			# skip this field if we don't want to show it
			if(isset($show_fields) && !isset($show_fields[$field])) continue;
			$label = isset($config['label']) ? $config['label'] : ucwords(str_replace('_',' ', $field));
			//var_dump($config);
			if(isset($patterns[$field]))
				$pattern = $patterns[$field];
			elseif(isset($patterns['_type.'.$config['type']]))
				$pattern = $patterns['_type.'.$config['type']];
			else
				$pattern = $patterns['*'];
			switch($config['type']) :
				case 'text':
					$pattern->_data()->data[':ixml'] = array(
						'label' => $label,
						'value' => $model->$field,
						'input' =>  $this->input->type('text')->class($pattern->attr['input_class'] ? $pattern->attr['input_class'] : false)->name($field)->size($config['length'])->maxlength($config['length'])->value($model->$field)
					);
					$string[$field] = (string) $pattern;
				break;
				case 'file':
					$pattern->_data()->data[':ixml'] = array(
						'label' => $label,
						'value' => $model->$field,
						'input' =>  $this->input->type('file')->class($pattern->attr['input_class'] ? $pattern->attr['input_class'] : false)->name($field)
					);
					$string[$field] = (string) $pattern;
				break;
				case 'number':
				case 'decimal':
					$pattern->_data()->data[':ixml'] = array(
						'label' => $label.' (numbers)',
						'value' => $model->$field,
						'input' => (string) $this->input->type('text')->class($pattern->attr['input_class'] ? $pattern->attr['input_class'] : false)->name($field)->size($config['length'])->maxlength($config['length'])->value($model->$field)
					);
					$string[$field] = (string) $pattern;
				break;
				case 'hidden':
					$string[$field] = (string) $this->input->type('hidden')->name($field)->value($model->$field);
				break;
				case 'dropdown':
					$sel = $this->select->name($field);
					foreach($config['options'] as $key => $option) {
						$a = $sel->option->value($key)->_text($option);
						if($model->$field == $key)
							$a->selected("selected");
					}
					$pattern->_data()->data[':ixml'] = array(
						'label' => $label,
						'value' => $model->$field,
						'input' => (string) $sel
					);
					$string[$field] = (string) $pattern;
				break;
			endswitch;
		}
		$pattern = $patterns['_submit'] ? $patterns['_submit'] : $patterns['*'];
		$pattern->_data()->data[':ixml'] = array(
			'label' => isset($this->attr['submit_text']) ? $this->attr['submit_text'] : 'Submit',
			'value' => isset($this->attr['submit_text']) ? $this->attr['submit_text'] : 'Submit',
			'input' => (string) $this->input->type('submit')->value(isset($this->attr['submit_text']) ? $this->attr['submit_text'] : 'Submit')
		);
		$string['_submit'] = (string) $pattern;
		$r = array();
		if(isset($show_fields)) {
			$r = array();
			foreach($show_fields as $key => $s) {
				$r[$key] = $string[$key];
			}
			
			$r['_submit'] = $string['_submit'];
		}
		if($fieldsets) {
			$r['_submit'] = $string['_submit'];
			$fr = array();
			foreach($fieldsets as $fieldset) {
				$rr = array();
				$show_fields = array_flip(explode(',', $fieldset['fields']));
				foreach($show_fields as $key => $s) {
					$rr[$key] = $string[$key];
					unset($r[$key]);
				}
				$fs = $this->fieldset;
				$fs->legend->_text($fieldset['label']);
				$fr[] = (string)$fs->_html(implode(' ', $rr));
			}
		}
		if($fr) $r = array_merge($fr, $r);
		elseif(!$r) $r = $string;
		$form->_html(implode(' ', $r));
		
		return (string) $form;
	}
	
	
}

