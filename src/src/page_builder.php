<?php

/**
 * The YAML Page Builder for IXML
 *
 * @package ixml
 * @author David Boskovic
 */
class e_Page_Builder {
	
	/**
	 * Store the IXML Object.
	 *
	 * @var string
	 **/
	public $html;
	
	/**
	 * List of quick references to key IXML elements
	 *
	 * @var array
	 */
	public $ref = array();
	
	/**
	 * Store the YAML configuration file
	 *
	 * @var string
	 */
	public $config;
	
	/**
	 * Array of configuration data for this page.
	 *
	 * @var string
	 */
	public $page;
	public $on_page;
	public $file;
	public $scopes;
	
	/**
	 * Initialize this object with a valid file path.
	 *
	 * @param string $file 
	 * @return void
	 * @author David Boskovic
	 */
	public function __construct($file) {
		
		# check to see if this file exists.
		if(!file_exists($file))
			return false;
		$this->file = $file;
		# load up the yaml interpretation of this file. (@todo caching)
		$file = e::helper('yaml')->file($file);
		
		# @remove
		//echo '<div style="font-family:courier;font-size:11px;white-space:pre">';
		
		if(is_numeric(e::$url->segment())) {
			e::$url->id = e::$url->segment();
			++e::$url->pointer;
		}
		# set the page segment to load.
		$page = !e::$url->segment() ? 'default' : e::$url->segment();
		$this->on_page = $page;
		
		# yaml config for this page
		$this->config = $file;
		$this->page =& $this->config[$page];

		
		$this->_init_ixml();
		$this->_init_scope();
		if($_GET['_module']) {
			$this->_init_modules();
			return true;	
		} 
		else {
			$this->_init_template();
			$this->_init_modules();
			$this->_init_nav();
			$this->_init_header();
			$this->_init_footer();	
		}
	}
	
	public function publish($return = 0) {
		if(!$return) echo $this->html;
		else return (string) $this->html;
	}
	
	private function _init_ixml() {
		$this->html = new InterfaceHelper;
	}
	
	private function _init_scope() {
		$scope = array();
		if(isset($this->config['*']['scope']) && is_array($this->config['*']['scope'])) {
			foreach($this->config['*']['scope'] as $key => $value) {
				$scope[$key] = $value;
			}
		}
		
		if(isset($this->page['scope']) && is_array($this->page['scope'])) {
			foreach($this->page['scope'] as $key => $value) {
				$scope[$key] = $value;
			}
		};
		$this->scopes = $scope;
		if(isset($this->page['authenticate']) || isset($this->config['*']['authenticate'])) {
			if(isset($this->config['*']['authenticate'])) $auth = ($this->config['*']['authenticate']);
			else $auth = $this->page['authenticate'];
			$authenticate = array(
				'el' => 'ixml:configure',
				'children' => array(
					array('el'=>'ixml:authenticate',
					'attr' => array('with' => $auth))
				)
			);
		
			$this->_add_tag($this->html, $authenticate);
		}
		foreach($scope as $as => $val) {
			$this->html->_attr(array('ixml:loop_source' => "$val as $as"));
		}
	}
	
	private function _init_nav() {
		$links = $this->ref['links'];
		//v($this->config);
		foreach($this->config as $slug => $page) {
			if($slug == '*') continue;
			$link = array(
				'el' => 'link',
				'attr' => array(
					'href' => e::$url->trace().($slug == 'default' ? '' : $slug),
					'icon' => $page['icon'],
					'pagelink' => $slug,
					'tab' => $this->on_page,
					'show' => '1'
				),
				'children' => array(
					$page['link_text']
				)
			);
			
			$this->_add_tag($links, $link);
		}
		
		$link = array(
			'el' => 'li',
			'attr' => array(
				'class' => '_config_new_page',
				'rel' => e::$url->trace(),
			),
			'children' => array(
				'New Page'
			)
		);
		
		$this->_add_tag($links, $link);
	}
	
	private function _init_header() {
		$this->ref['head']->attr['title'] = $this->page['title'];
		
		$this->ref['header']->_html($this->page['title'],$this->extract_vars($this->page['title']));
		if($this->page['description']) $this->ref['description']->_html($this->page['description'],$this->extract_vars($this->page['description']));
	}
	
	/**
	 * Get a list of modules that can be inserted into this scope, cache the results by file update time.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	private function _available_modules($select = false) {
		$modules = cache::get('portal', 'modules');
		if(!$modules) $modules = array();
		$dir = ROOT_PORTALS."/".e::$url->portal."/modules/";
		
		$module_tag = array(
			'el' => 'ixml:module',
			'attr' => array(
				'name' => false,
			)	
		);
		$d = dir($dir); 
		$target = new InterfaceHelper;
		while (false!== ($filename = $d->read())) { 
			if($filename == '.' || $filename == '..' || substr($filename,0,1) == '.') continue;
			$time = filemtime($dir.$filename);
			$m = substr($filename,0,-5);
			if((isset($modules[$m]) && $time > $modules[$m]['last_update']) || !isset($modules[$m])) {
				$module_tag['attr']['name'] = $m;
				$mo = $this->_add_tag($target,$module_tag);
				if(!$mo->_ignore()){
					$o = $mo->_valid_owners();
					$opt = $mo->_option_values();
					$modules[$m] = array(
						'last_update' => $time,
						'options' => $opt,
						'owners' => $o
					);
				}
			}
		} 
		$d->close();
		cache::store('portal', 'modules', $modules);
		$om = array();
		foreach($modules as $module => $info) {
			if(in_array(NULL, $info['owners'])) {
				$om[$module] = $info;
			}
			else {
				foreach($info['owners'] as $owner) {
					if(isset($this->scopes[$owner])) {
						$om[$module] = $info;
						break;
					}
				}
			}
		} 
		# remove any modules that we already have
		foreach($this->page['modules'] as $module) {
			if(isset($om[$module['slug']])) $om[$module['slug']]['used'] = true;
			else $om[$module['slug']]['used'] = false;
		}
		if($select) {
			if(!$om) return false;
			$html = '<select name="module">';
			foreach($om as $am => $info) {
				$name = $info['used'] ? '(USED) '.$info['options']['_name'] : $info['options']['_name'];
				$html .= "<option value='$am'>$name</option>";
			}
			$html .= "</select>";
			return $html;
		}
		return $om;
	}
	private function _bump_module_positions($target_area, $position) {
		foreach($this->page['modules'] as &$module) {
			if($module['_target_area'] == $target_area && $module['_position'] >= $position) {
				$module['_position'] += 1;
			}
		}
		
	}
	private function _init_modules() {
		$admin_tag = array(
			'el' => 'div',
			'attr' => array(
				'class' => '_momentum_module',
				'rel' => false
			)	
		);
		
		$module_tag = array(
			'el' => 'ixml:module',
			'attr' => array(
				'name' => false,
			)
		);
		
		if($_GET['_new_module']) {
			$ams = $this->_available_modules();
			$opt_vals = $ams[$_GET['module']]['options'];
			
			$module = $opt_vals;
			$module['slug'] = $_GET['module'];
			$module['_target_area'] = $_GET['_target_area'];
			$module['_position'] = $_GET['_position'] ? $_GET['_position'] : 1;
			$this->_bump_module_positions($_GET['target_area'],$_GET['_position']);
			$this->page['modules'][] = $module;
			$saved = e::helper('yaml')->save($this->file, $this->config);
			if($saved) echo json_encode(array('status' => 1, 'message' => "Updated Configuration"));
			else echo json_encode(array('status' => 0, 'message' => "Could not write to file."));
			die;
		}
		$i = 1;
		
		@usort($this->page['modules'],'usort_modules');
			$c = $admin_tag['attr']['class'];
		foreach($this->page['modules'] as &$module) {
			if($_GET['_module'] && $_GET['_module'] != md5($module['slug'].$i)) {++$i;continue;}
			$a = false;
			$module['url'] = e::$url->trace().$this->on_page.'?_module='.md5($module['slug'].$i);
			$module_tag['attr']['name'] = $module['slug'];
			$module_tag['attr']['_config'] = $module;
			$admin_tag['attr']['rel'] = $module['url'];
			$admin_tag['attr']['class'] = $c;
			if(isset($module['collapse']) && $module['collapse']) $admin_tag['attr']['class'] .= ' _momentum_collapse';
			$target = isset($this->ref[$module['_target_area']]) ? $this->ref[$module['_target_area']] : $this->html;
			$a = $this->_add_tag($target, $admin_tag);

			$a->_html('<span class="_config_show_options iconic toggle_link">w</span>');
			$a->_html('<span class="_config_remove_module iconic toggle_link">x</span>');
			$m = $this->_add_tag($a,$module_tag);
			
			if($_GET['_update_config']) {
				$opt_vals = $m->_option_values();

				foreach($opt_vals as $key => $val) {
					if(isset($_GET[$key])) {
						$opt_vals[$key] = $_GET[$key];
					} else {
						$opt_vals[$key] = false;
					}
				}
				$module = $opt_vals;
				$saved = e::helper('yaml')->save($this->file, $this->config);
				if($saved) echo json_encode(array('status' => 1, 'message' => "Updated Configuration"));
				else echo json_encode(array('status' => 0, 'message' => "Could not write to file."));
				die;
			}
			if($_GET['_remove']) {
				unset($this->page['modules'][$i-1]);
				$saved = e::helper('yaml')->save($this->file, $this->config);
				if($saved) echo json_encode(array('status' => 1, 'message' => "Deleted"));
				else echo json_encode(array('status' => 0, 'message' => "Could not write to file."));
				die;
				
			}
			$a->_html($m->_admin_form(md5($module['slug'].$i)));
			//echo $a->children[]; die;
			++$i;
			
			if(!$_GET['_module']) {
				$target_area = $module['_target_area'];
				$position = $module['_position']+1;
				/*$target->_html('<div class="_config_new_module" rel="'.e::$url->trace().$this->on_page.'"><div class="_config_new_module_label">+ Add Module</div><div class="_config_new_module_contents"><form class="_config_new_module_form"><input type="hidden" name="_target_area" value="'.$target_area.'" /><input type="hidden" name="_position" value="'.$target_area.'" /><label>Select An Available Module To Insert</label>'.$this->_available_modules('select').'<div style="padding-top:10px"><input type="submit" value="Insert Module" /></div></form><div class="clear"></div></div></div>');
			*/
			}
			
			if($_GET['_module'] && $_GET['_module'] == $module['slug']) break;
		}
	}
	
	private function _init_footer() {
		
	}
	private function _init_template() {

		$dir = e::$url->portal ? ROOT_PORTALS.'/'.e::$url->portal.'/patterns/' : '';
		
		Interface_Parser::register_callback(function($event, $el, $data) {
			
			switch($event) {
				case 'attr':
					if($el->attr['ref']) {
						$data['page']->ref[$el->attr['ref']] = $el;
					}
				break;
			}
		}, array('page' => $this));
		
		$parser = new Interface_Parser($dir.'default.ixml', $this->html);
		//Interface_Parser::unregister_callback();
		
		/*$target = $this->ref['column_one'];		
		if($this->_available_modules()) $target->_html('<div class="_config_new_module" rel="'.e::$url->trace().$this->on_page.'"><div class="_config_new_module_contents"><form class="_config_new_module_form"><input type="hidden" name="_target_area" value="column_one" /><label>Select An Available Module To Insert</label>'.$this->_available_modules('select').'<div style="padding-top:10px"><input type="submit" value="Insert Module" /></div></form></div></div>');
		else $target->_html('<div class="_config_new_module" rel="'.e::$url->trace().$this->on_page.'"><div class="_config_new_module_contents"><label>No Available Modules To Add</label></div></div>');
		
		$target = $this->ref['column_two'];
		if($this->_available_modules()) $target->_html('<div class="_config_new_module" rel="'.e::$url->trace().$this->on_page.'"><div class="_config_new_module_contents"><form class="_config_new_module_form"><input type="hidden" name="_target_area" value="column_two" /><label>Select An Available Module To Insert</label>'.$this->_available_modules('select').'<div style="padding-top:10px"><input type="submit" value="Insert Module" /></div></form></div></div>');
		else $target->_html('<div class="_config_new_module" rel="'.e::$url->trace().$this->on_page.'"><div class="_config_new_module_contents"><label>No Available Modules To Add</label></div></div>');
*/
	}
	
	
	private function extract_vars($content, $special = false) {
		
		// parse out the variables
		preg_match_all(
			$special ? "/{(\%[\w:|.\,\(\)\[\]\/\-\% ]+?)}/" : "/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
	}
	
	private function _add_tag($el, $tag) {
		if(is_string($tag)) {
			$el->$tag_name->_html($tag);
			return true;	
		}
		$tag_name = $tag['el'];
		$tag_attributes = $tag['attr'];
		$n = $el->$tag_name;
		//debug_print_backtrace();
		$n->_attr($tag_attributes);
		if($tag['ref']) {
			$this->ref[$tag['ref']] = $n;
		}
		if($tag['children']) {
			foreach($tag['children'] as $tagt) {
				$this->_add_tag($n, $tagt);
			}
		}
		//var_dump($n);
		return $n;
	}
}

function usort_modules($a, $b)
{
    if ($a['_position'] == $b['_position']) {
        return 0;
    }
    return ($a['_position'] < $b['_position']) ? -1 : 1;
}