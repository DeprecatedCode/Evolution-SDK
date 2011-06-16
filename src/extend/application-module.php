<?php

define('CONN_DIR_INCOMING', 'INCOMING');
define('CONN_DIR_OUTGOING', 'OUTGOING');
define('CONN_TYPE_ALL', false);
define('CONN_TYPE_PRIMARY', 'primary');
define('CONN_TYPE_SECONDARY', 'secondary');
define('CONN_TYPE_CONTROL', 'control');
define('CONN_RETURN_FIRST', true);
define('CONN_RETURN_ALL', false);
define('CONN_FILTER_ALL', '*');

class App_Module {
	
	public $model;
	public $_name;
	public $_slug;
	
	public static $_cache = array();
	public static $_cache_size = array();
	
	public function __construct($id_or_array = false) {
		if(!isset($this->_map)) e::fault(100,'application_module_no_mapvar');
		if(!isset($this->_table)) e::fault(100,'application_module_no_tablevar');
		$this->model = e::helper('mysql_model', $this->_table, $id_or_array);
	}
	
	public function _self_link($prefix = '') {
		
		if(!isset($this->_name)) {
			$a = explode('.', $this->_map, 2);
			$b = ucwords(str_replace('_', ' ', $a[1]));
			$this->_name = $b;
			$this->_slug = $a[1];
		}
		
		return '<span class="mtag" style="cursor:default">' . $this->_name . '</span><a href="'.$prefix.'/'.$this->_slug.'/'.$this->id.'">'.$this->name.'</a>';
	}
	public function to_api() {
		$app = substr($this->_map, 0,strpos($this->_map,'.'));	
		$model = substr($this->_map, strpos($this->_map,'.')+1);
		
		$data = $this->model->get_array();
		$configure = e::app($app)->configure->api;
		$r = array();
		if(isset($configure['models'][$model])) {
			foreach((array) $configure['models'][$model] as $field => $type) {
				if(!array_key_exists($field,$data)) continue;
				$val = $data[$field];
				switch($type) {
					case 'integer':
						$val = (int) $val;
					break;
					case 'decimal':
					case 'float': 
						$val = (float) $val;
					break;
					case 'string':
					default:
						$val = (string) $val;
					break;
				}
				$r[$field] = $val;
			} 
		}
		return $r;
	}
	public function __get($var) {
		return $this->model->$var;
	}

	public function __set($var, $val) {
		$this->model->$var = $val;
	}

	public function __isset($var) {
		return isset($this->model->$var);
	}

	public function __call($method, $args) {
		$app = substr($this->_map,0,strpos($this->_map,'.')).'.*';
		if(isset($this->_connections[$method])) {
			return $this->_connection($method, $this->_connections[$method]);
		}
		elseif(isset(e::$_deprecated_cache['module_hooks'][$this->_map][$method])) {
			return e::app($method, $this);
		}
		elseif(isset(e::$_deprecated_cache['module_hooks'][$app][$method])) {
			return e::app($method, $this);			
		}
		else {
			// Why throw errors when this causes the entire page to break when any mysql_helper module field is null? (Nate)
			return null;
			e::fault(100,'application_no_connected_module', array('connect_to' => $this->_map.'.'.$method,'map'=>$this->_map));
		}
	}
	
	protected function _connection($index, $map) {
		
		# if we've already loaded this exact result once, let's not do it again
		if($this->_check_cache($index))
			return $this->_cache($index);
		$this->_prepare_cache($index);
		$result = $this->module_connections(CONN_DIR_OUTGOING, $map);
		return $this->_set_cache($index, $result);
	}

	public function __toString() {
		return $this->model->name;
	}
	
	public function _check_cache($index) {
		if(isset($this->id) AND isset(self::$_cache[$this->module_name][$this->id][$index]))
			return true;
		return false;
	}
	public function _cache($index) {
		if($this->_check_cache($index))
			return self::$_cache[$this->module_name][$this->id][$index];
	}
	public function _count_cache($index) {
		if($this->_check_cache($index))
			return count(self::$_cache[$this->module_name][$this->id][$index]);
		return false;
	}
	public function _cache_size($index) {
		if($this->_check_cache($index)) {
			$s = self::$_cache_size[$this->module_name][$this->id][$index];
			return (object) array('kb' => $s/1000,'b' => $s,'avg' => ($s /$this->_count_cache($index))/1000);
		}
		return false;
	}
	
	public function _prepare_cache($index) {
		if(!self::$_cache_size[$this->module_name][$this->id][$index])
		self::$_cache_size[$this->module_name][$this->id][$index]['start'] = memory_get_usage();
	}
	
	public function _set_cache($index, $value, $bool_only = false) {
		self::$_cache_size[$this->module_name][$this->id][$index] = memory_get_usage()-self::$_cache_size[$this->module_name][$this->id][$index]['start'];
		self::$_cache[$this->module_name][$this->id][$index] = $value;
		return $bool_only ? true : self::$_cache[$this->module_name][$this->id][$index];
	}
	
	public function prepare_connection_query($query = false) {
		//debug_print_backtrace();
		$this->_connection_query = $query;
		return $this;
	}
	
	public $_connection_query = false;
	protected $_module_limit_start = 0;
	protected $_module_limit_limit = false;
	public function module_paging($page =1, $limit = 10) {
		$page = (int) $page;
		$page = $page > 0 ? $page : 1;
		$limit = (int) $limit;
		$this->_module_limit_start = $page * $limit - $limit;
		$this->_module_limit_limit = $limit;
		return $this;
	}
	public function module_count() {
		$this->_count_module = true;
		return $this;
	}
	private $_count_module = false;
	public function module_connections($direction = CONN_DIR_INCOMING, $to = CONN_FILTER_ALL, $type = CONN_TYPE_ALL, $first = CONN_RETURN_ALL) {
		if(!$this->_map) die('Trying to access connections on a module that does not have a _map var set.');
		
		if(!$this->_count_module && $to != CONN_FILTER_ALL && strpos($to, '|') === false) {
			$t = str_replace('.','_', $to);
			$at = ', '.$t;
			$st = "_connections.*, `$t`.*";
		} elseif($this->_count_module) {
			$this->_count_module = false;
			$t = str_replace('.','_', $to);
			$at = ', '.$t;
			$st = 'count(*) as `count`';
			$count = true;
		} else {
			$t = '';
			$at = '';
			$st = '*';
		}
	
		$connections = 
			$direction == 'INCOMING' ?
				e::db('mysql')->query("SELECT $st FROM `_connections` $at WHERE ".($t ? '_connections.id_a=id AND' :'')." module_b ='$this->_map' AND id_b='$this->id'".
					($to != CONN_FILTER_ALL ? self::_connections_filter_fragment($direction,$to) : '').
					($type ? " AND type='$type'":'').
					($this->_connection_query ? ' '.$this->_connection_query:'').
					($this->_module_limit_limit ? ' LIMIT '.$this->_module_limit_start.','.$this->_module_limit_limit : ''))
			:
				e::db('mysql')->query("SELECT $st FROM `_connections` $at WHERE ".($t ? '_connections.id_b=id AND' :'')." module_a ='$this->_map' AND id_a='$this->id'".
					($to != CONN_FILTER_ALL ? self::_connections_filter_fragment($direction,$to) : '').
					($type ? " AND type='$type'":'').
					(!empty($this->_connection_query) ? ' '.$this->_connection_query:'').
					($this->_module_limit_limit ? ' LIMIT '.$this->_module_limit_start.','.$this->_module_limit_limit : ''));
		#reset limit fragment
		if($this->_module_limit_limit) $this->_module_limit_limit = false;
		
		$items = array();
		$this->_connection_query = false;
		if($count) return $connections->row();
		while($row = $connections->row()) {
			if($direction == CONN_DIR_INCOMING) {
				list($map_app, $map_module) = explode('.', $row['module_a']);
				//var_dump($map_app,$map_module);
				$items[] = e::app($map_app)->$map_module($t ? $row : $row['id_a']);
			}
			else {
				list($map_app, $map_module) = explode('.', $row['module_b']);
				//var_dump($t ? $row : $row['id_b']);
				$items[] = e::app($map_app)->$map_module($t ? $row : $row['id_b']);
			}
		}
		return $first ? $items[0] : $items;
	}
	
	public function module_copy_connections($target,  $direction = CONN_DIR_INCOMING, $to = CONN_FILTER_ALL, $type = CONN_TYPE_ALL) {
		if(!$this->_map) die('Trying to access connections on a module that does not have a _map var set.');
		if($to != CONN_FILTER_ALL) {
			$filter = explode('|',$to);
			foreach($filter as $item) {}
		}
		$connections = 
			$direction == CONN_DIR_INCOMING ?
				e::db('mysql')->select('_connections', "WHERE module_b ='$this->_map' AND id_b='$this->id'".
					($to != CONN_FILTER_ALL ? self::_connections_filter_fragment($direction,$to) : '').
					($type ? " AND type='$type'":''))
			:
				e::db('mysql')->select('_connections', "WHERE module_a ='$this->_map' AND id_a='$this->id'".
					($to != CONN_FILTER_ALL ? self::_connections_filter_fragment($direction,$to) : '').
					($type ? " AND type='$type'":''));
				
		$items = array();		
		$new_items = array();
		$query = "REPLACE INTO `_connections` (`module_b`,`id_b`,`module_a`,`id_a`,`type`) VALUES ";
		while($row = $connections->row()) {
			if($direction == CONN_DIR_INCOMING) {
				$new_items[] = vsprintf("('%s',%u,'%s',%u,'%s')", array($target[0], $target[1], $row['module_a'], $row['id_a'], $row['type']));
			}
			else {
				$new_items[] = vsprintf("('%s',%u,'%s',%u,'%s')", array($row['module_b'], $row['id_b'], $target[0], $target[1], $row['type']));
			}
		}
		$implode =  implode(',',$new_items);
		$query .= $implode.';';
		if($implode) e::db('mysql')->query($query);
		return true;
		
	}
	
	public function module_is_connected($module_map, $module_id, $direction = CONN_DIR_OUTGOING, $type = CONN_TYPE_ALL) {
		if(!$this->_map) die('Trying to access connections on a module that does not have a _map var set.');
		$connections = 
			$direction == CONN_DIR_INCOMING ?
				e::db('mysql')->select('_connections', "WHERE module_b ='$this->_map' AND id_b='$this->id' AND module_a='$module_map' AND id_a = '$module_id'".
					($type ? " AND type='$type'":''))
			:
				e::db('mysql')->select('_connections', "WHERE module_a ='$this->_map' AND id_a='$this->id' AND module_b='$module_map' AND id_b = '$module_id'".
					($type ? " AND type='$type'":''));
		if($connections->row()) return true;
		else return false;
		
	}
	
	public function _id_map() {
		return $this->_map . '(' . $this->id . ')';
	}
	
	public function module_connect($module_map, $module_id, $direction = CONN_DIR_OUTGOING, $type = CONN_TYPE_SECONDARY) {

		$vars = array();
		switch($direction) {
			case CONN_DIR_OUTGOING:
				$vars['module_a'] = $this->_map;
				$vars['id_a'] = $this->id;
				$vars['module_b'] = $module_map;
				$vars['id_b'] = $module_id;
			break;
			case CONN_DIR_INCOMING:
				$vars['module_b'] = $this->_map;
				$vars['id_b'] = $this->id;
				$vars['module_a'] = $module_map;
				$vars['id_a'] = $module_id;
			break;			
		}
			
		$vars['type'] = $type;
		e::db('mysql')->replace('_connections',$vars);
		return $this;
	}
	public function module_disconnect($module_name, $module_id, $direction = CONN_DIR_OUTGOING, $type = CONN_TYPE_SECONDARY) {		
		e::db('mysql')->query("DELETE FROM _connections WHERE module_a = '%s' AND  id_a=%u AND module_b = '%s' AND id_b = %u",array($this->_map, $this->id, $module_name, $module_id));
	}
	public function module_disconnect_type($module_name, $direction = CONN_DIR_OUTGOING, $type = CONN_TYPE_SECONDARY) {	
		if($direction == CONN_DIR_OUTGOING)
			e::db('mysql')->query("DELETE FROM _connections WHERE module_a = '%s' AND  id_a=%u AND module_b = '%s'",array($this->_map, $this->id, $module_name));
		if($direction == CONN_DIR_INCOMING)
			e::db('mysql')->query("DELETE FROM _connections WHERE module_b = '%s' AND  id_b=%u AND module_a = '%s'",array($this->_map, $this->id, $module_name));
	}
	public function module_disconnect_all() {		
		e::db('mysql')->query("DELETE FROM _connections WHERE (module_a = '%s' AND  id_a=%u) || (module_b = '%s' AND id_b = %u)",array($this->_map, $this->id, $this->_map, $this->id));
	}
	
	public function _form_config() {
		$app = substr($this->_map, 0,strpos($this->_map,'.'));
		$model = substr($this->_map, strpos($this->_map,'.')+1);
		$db = e::db('mysql')->get_fields($this->_table);
		if(file_exists(ROOT_APPLICATIONS.'/'.$app.'/configure/forms.yaml')) {
			$conf = e::helper('yaml')->file(ROOT_APPLICATIONS.'/'.$app.'/configure/forms.yaml');
			if(isset($conf[$model])) $conf = $conf[$model];
			else $conf = array();
		}
		$output = array();
		foreach($db as $field => $c) {
			$length = strpos($c['Type'],'(') > 0 ? substr($c['Type'],strpos($c['Type'],'(')+1,strpos($c['Type'],')')-strpos($c['Type'],'(')-1) : false;
			if(strpos($c['Type'],'varchar') === 0) {
				if($length) $output[$field]['length'] = $length;
				$output[$field]['type'] = 'text';
			}
			elseif(strpos($c['Type'],'int') === 0) {
				if($length) $output[$field]['length'] = $length;
				$output[$field]['type'] = 'number';
			}
			elseif(strpos($c['Type'],'decimal') === 0) {
				if($length) $output[$field]['decimals'] = explode(',', $length);
				$output[$field]['type'] = 'decimal';
			}
			elseif(strpos($c['Type'],'enum') === 0 || strpos($c['Type'],'set') === 0) {
				if($length) $length = explode(',', $length);
				foreach($length as $item) {
					$options[trim($item, "'")] = ucwords(trim($item, "'"));
				}
				$output[$field]['options'] = $options;
				$output[$field]['type'] = 'dropdown';
			}
			elseif(strpos($c['Type'],'blob') !== false) {
				$output[$field]['type'] = 'file';
			}
			elseif(strpos($c['Type'],'text') !== false)
				$output[$field]['type'] = 'textarea';
		}
		if($conf)
			foreach($conf as $field => $c) {
				if(isset($output[$field])) $output[$field] = array_merge($output[$field], $c);
				//if(isset($output[$field]) && is_array($c)) $output[$field] = array_merge($output[$field], $c);
			}
			//var_dump($output);die;
		return $output;
	}
	protected static function _connections_filter_fragment($direction, $filter) {		
			if($filter != CONN_FILTER_ALL) {
				$filter = explode('|',$filter);
				$m = $direction == CONN_DIR_INCOMING ? 'module_a' : 'module_b';
				$fragments = array();
				foreach($filter as $item) {
					$fragments[] = "$m = '$item'";
				}
				$fragment = implode('OR', $fragments);
				return " AND ($fragment)";
			}
			else
				return '';
	}
	
}


class App_Module_MySQL extends App_Module {
	
}