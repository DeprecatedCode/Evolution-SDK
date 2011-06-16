<?php

/**
 * Component
 *
 * @package base
 * @author David Boskovic
 **/
class Component
{
	
	public static $_cache = array();
	public static $_cache_size = array();
	public static $_cache_owner = 'none';
	private $_cache_disabled = false;
	public $owner = false;
	protected $_lists = array();
	protected $_models = array();
	protected $_page_length = 20;
	

	/**
	 * Initialize the Application
	 *
	 * @param string $owner 
	 */
	public function __construct($owner = false) {
		
		$this->configure = new e_Configure(ROOT_COMPONENTS.'/'.$this->_name.'/configure');

		# save the owner instance
		if(is_object($owner)) {
			$this->owner = $owner;
			
			# set a unique owner key for the cache. eg: application.module.id
			$this->_cache_owner = $owner->_map.'.'.$owner->id;
		}
		
		# save the owner instance
		elseif(method_exists($this, '_default_owner')) {
			$this->_default_owner($owner);
			
			# set a unique owner key for the cache. eg: application.module.id
			if($this->owner) 				
			$this->_cache_owner = $this->owner->_map.'.'.$this->owner->id;
		}
	}
	
	protected function _check_cache($index) {
		if(!$this->_cache_disabled AND isset($this->_cache_owner) AND isset(self::$_cache[$this->_application][$this->_cache_owner][$index]))
			return true;
		$this->_cache_disabled = false;
		return false;
	}
	protected function _cache($index) {
		if($this->_check_cache($index))
			return self::$_cache[$this->_application][$this->_cache_owner][$index];
	}
	protected function _prepare_cache($index) {
		if(!self::$_cache_size[$this->_application][$this->_cache_owner][$index])
		self::$_cache_size[$this->_application][$this->_cache_owner][$index]['start'] = memory_get_usage();
	}
	protected function _set_cache($index, $value, $bool_only = false) {
		self::$_cache_size[$this->_application][$this->_cache_owner][$index] = memory_get_usage()-self::$_cache_size[$this->_application][$this->_cache_owner][$index]['start'];
		self::$_cache[$this->_application][$this->_cache_owner][$index] = $value;
		return $bool_only ? true : self::$_cache[$this->_application][$this->_cache_owner][$index];
	}
	public function no_cache() {
		$this->_cache_disabled = true;
		return $this;
	}
	protected function _count_cache($index) {
		if($this->_check_cache($index))
			return count(self::$_cache[$this->_application][$this->_cache_owner][$index]);
		return false;
	}
	protected function _cache_size($index) {
		if($this->_check_cache($index)) {
			$s = self::$_cache_size[$this->_application][$this->_cache_owner][$index];
			return (object) array('kb' => $s/1000,'b' => $s,'avg' => ($s /$this->_count_cache($index))/1000);
		}
		return false;
	}
	protected function _list($page, $page_length, $sort_by, $table, $map, $module) {
			
			$page = (int) (is_numeric($page) ? $page : 1);
			$page_length = (int) (is_numeric($page_length) ? $page_length : $this->_page_length);

			$conf_key = md5($page.$page_length.$sort_by);
			# if we've already loaded this exact result once, let's not do it again
			if($this->_check_cache($table.'_'.$conf_key))
				return $this->_cache($table.'_'.$conf_key);

			# setup paging
			$page = is_numeric($page) && $page > 0 ? $page : 1;
			$start =  ($page * $page_length)-$page_length;

			# prepare cache.
			$this->_prepare_cache($table.'_'.$conf_key);

			# if we have an owner, run a query for the pages
			if(!$this->owner) {
	 			$pages = e::$db->mysql->select($table, ($sort_by?"ORDER BY $sort_by ":'')."LIMIT $start, $page_length");

				$result = array();

				if(!class_exists($module)) {				
					e::$error->fault(100, 'application_module_not_exist',array('application_name' => $this->_application, 'map' => $map));
				}
				while($page = $pages->row()) {
					$result[] = new $module($page, $this);
				}
			# otherwise get the linked connections
	    	} else {
				$result = $this->owner->prepare_connection_query(($sort_by?"ORDER BY $sort_by ":'')."LIMIT $start, $page_length")->module_connections(CONN_DIR_OUTGOING, $map, CONN_TYPE_ALL);
	    	}

			# return the cached results.
			return $this->_set_cache($table.'_'.$conf_key, $result);
	}
	
	public function _query_list($table = false, $map = false, $page = false, $page_length = false, $custom_query = false, $sort_by = false) {
		
		# fault if no table is passed
		if(!$table) e::$error->fault(100, 'application_query_list_no_table',array('application_name' => $this->_application, 'map' => $map));
		
		# fault if no map is passed
		if(!$map) e::$error->fault(100, 'application_query_list_no_map',array('application_name' => $this->_application, 'map' => $map));
		
		$page = (int) (is_numeric($page) ? $page : 1);
		$page_length = (int) (is_numeric($page_length) ? $page_length : $this->_page_length);

		$conf_key = md5($table.$map.$page.$page_length.$custom_query.$sort_by);
		
		# if we've already loaded this exact result once, let's not do it again
		if($this->_check_cache($table.'_'.$conf_key))
			return $this->_cache($table.'_'.$conf_key);

		# setup paging
		$page = is_numeric($page) && $page > 0 ? $page : 1;
		$start = ($page * $page_length)-$page_length;

		# prepare cache.
		$this->_prepare_cache($table.'_'.$conf_key);
		
		$pages = e::$db->mysql->select($table, ($custom_query ? $custom_query.' ' : '').($sort_by?"ORDER BY $sort_by ":'')."LIMIT $start, $page_length");

		$result = array();
		list($ma, $mm) = explode('.',$map);
		$table = str_replace('.','_',$map);
		$module = 'Component_'.$ma.'__Model_'.$mm;

		if(!class_exists($module)) {				
			e::$error->fault(100, 'application_module_not_exist',array('application_name' => $this->_application, 'map' => $map));
		}
		while($page = $pages->row()) {
			$result[] = new $module($page, $this);
		}
		return $result;
	}
	
	public function __call($method, $args) {
		if(isset($this->_lists[$method])) {
			$map = $this->_lists[$method];
			list($ma, $mm) = explode('.',$map);
			$module = 'Component_'.$ma.'__List_'.$mm;
			return new $module($this);
		}
		elseif(isset($this->_models[$method])) {
			$map = $this->_models[$method];
			list($ma, $mm) = explode('.',$map);
			$table = str_replace('.','_',$map);
			$module = 'Component_'.$ma.'__Model_'.$mm;
			if(!class_exists($module)) {				
				e::$error->fault(100, 'application_module_not_exist',array('application_name' => $this->_application, 'map' => $map));
			}
			return new $module($args[0], $this);
		}
		else {
			e::$error->fault(100, 'application_method_not_exist',array('application_name' => $this->_application, 'method' => $method));
			die("Attempting to access a module or a list [$method] that hasn't been declared.");
		}
	}
}