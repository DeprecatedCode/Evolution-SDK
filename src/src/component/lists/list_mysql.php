<?php
/**
 * MySQL Iterator Query Class
 *
 * @package default
 * @author David Boskovic
 */
class Component_List_MySQL implements Iterator, Countable {
	
	/**
	 * The current iterator position.
	 *
	 * @var integer
	 */
	private $position = 0;

	/**
	 * The table this list is for.
	 *
	 * @var string
	 */
	protected $_table = false;

	/**
	 * The component object
	 *
	 * @var object
	 */
	protected $component = false;
	
	/**
	 * The Evolution Map to this module.
	 *
	 * @var string
	 */
	protected $_map = false;
	
	/**
	 * The array of results after the query has been executed.
	 *
	 * @var array
	 */
	public $_results = array();
	
	/**
	 * The query conditions.
	 *
	 * @var array
	 */
	protected $_query_cond = array();
	
	/**
	 * Sorting
	 *
	 * @var array
	 */
	protected $_order_cond = array();
	
	/**
	 * Limiting
	 *
	 * @var string
	 */
	protected $_limit = false;
	protected $_limit_size = false;
	
	/**
	 * The cached value for the count of items in the resultset.
	 *
	 * @var string
	 */
	protected $_count = 0;
	protected $_count_all = 0;
	
	/**
	 * Default page length.
	 *
	 * @var string
	 */
	protected $_page_length = 5;
	
	/**
	 * The fields to select in this query.
	 *
	 * @var string
	 */
	protected $_fields_select = '*';
	
	/**
	 * The tables to select in this query.
	 *
	 * @var string
	 */
	protected $_tables_select;
	
	/**
	 * What page of results are we currently on?
	 *
	 * @var string
	 */
	protected $_on_page = 1;
	
	/**
	 * Initialize
	 *
	 * @author David Boskovic
	 */
	public function __construct($component) {
		if($this->_table == null) e::fault('list_mysql_no_table');
		$this->component = $component;
		$this->_tables_select = "`$this->_table`";
		$this->initialize();
	}
	
	/**
	 * Function for inheriting classes to use.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	protected function initialize() {
		
	}
	
	/**
	 * Add a condition to filter the result.
	 *
	 * @param string $field 
	 * @param string $value 
	 * @param bool $verify 
	 * @return $this
	 * @author David Boskovic
	 */
	public function condition($field, $value, $verify = false) {
		//var_dump($field,$value);
		$signal = strpos($field, ' ') ? substr($field, strpos($field, ' ')+1) : '=';
		$field = strpos($field, ' ') ? substr($field, 0, strpos($field, ' ')) : $field;
		$value = (strpos($value, ':') === 0 && ctype_alpha(substr($value,1)) == true) ? '`'.substr($value, 1).'`' : $value;
		$value = is_null($value) || is_numeric($value) || strpos($value, '`') === 0 ? $value : "'$value'";
		if(is_null($value)) $value = 'NULL';
		$field = strpos($field,'`') === 0 ? $field : "`$this->_table`.`$field`";
		if($verify) return "$field $signal $value";
		$this->_query_cond[] = "$field $signal $value";
		return $this;
	}
	
	/**
	 * Clear query
	 */
	public function clear_query() {
		$this->_query_cond = array();
		
		return $this;
	}
	
	/**
	 * Add a connection condition to filter the result.
	 *
	 * @param string $map
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function has_connection($map, $direction = 'forward', $verify = false) {
		// Allow both connection directions
		if($direction == 'forward') {
			$a = 'a'; $b = 'b';
		} else if($direction == 'reverse') {
			$a = 'b'; $b = 'a';
		} else {
			throw new Exception('Cannot call <code>List_MySQL->has_connection($map, $direction = "forward", $verify = false)</code> with <code>$direction</code> anything other than <code>forward</code> or <code>reverse</code>');
		}
		
		$conn_select = "SELECT `c`.`id_$a` FROM `_connections` `c`";
		
		$conn_cond = "`c`.`module_$a` = '$this->_map'";
		if($map)
			$conn_cond .= " AND `c`.`module_$b` = '$map'";
		
		if($verify) return "`id` IN ($conn_select WHERE $conn_cond)";
		$this->_query_cond[] = "`id` IN ($conn_select WHERE $conn_cond)";
		return $this;
	}
	
	/**
	 * Add a connection condition to filter the result.
	 *
	 * @param string $map
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function specific_connection($map, $id, $direction = 'forward', $exclude = false) {
		// Allow both connection directions
		if($direction == 'forward') {
			$a = 'a'; $b = 'b';
		} else if($direction == 'reverse') {
			$a = 'b'; $b = 'a';
		} else {
			throw new Exception('Cannot call <code>List_MySQL->has_connection($map, $direction = "forward", $verify = false)</code> with <code>$direction</code> anything other than <code>forward</code> or <code>reverse</code>');
		}
		
		$conn_select = "SELECT `c`.`id_$a` FROM `_connections` `c`";
		
		$conn_cond = "`c`.`module_$a` = '$this->_map'";
		if($map)
			$conn_cond .= " AND `c`.`module_$b` = '$map' AND id_$b = $id";
		$not = $exclude ? 'NOT' : '';
		if($verify) return "`id` $not IN ($conn_select WHERE $conn_cond)";
		$this->_query_cond[] = "`id` $not IN ($conn_select WHERE $conn_cond)";
		return $this;
	}
	/**
	 * Add a tag condition to filter the result.
	 *
	 * @param App_Taxonomy_Tag $tag
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function has_tag($tag, $verify = false) {
		
		if(!$tag->id)
			return $this;
			
		$tag_query = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(map, '(', -1), ')', 1) FROM `taxonomy_item_tag`
			WHERE `map` LIKE '$this->_map%' AND `tag` = $tag->id";
		if($verify) return "`id` IN ($tag_query)";
		$this->_query_cond[] = "`id` IN ($tag_query)";
		return $this;
	}
	
	public function add_select_field($field) {
		$this->_fields_select .= ", $field";
	}

	/**
	 * Add a tag condition to filter the result.
	 *
	 * @param App_Taxonomy_Tag $tag
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function union_with_tag($tag, $primary, $table, $map, $verify = false) {
		
		if(!$tag->id)
			return $this;
		
		$tag_query = "SELECT `$primary` FROM `$table` `i`, `taxonomy_item_tag` `t`
			WHERE `t`.`map` = CONCAT('$map(', `i`.`id`, ')') AND `t`.`tag` = $tag->id";
		if($verify) return "`id` IN ($tag_query)";
		$this->_query_cond[] = "`id` IN ($tag_query)";
		return $this;
	}

	/**
	 * Add a tag condition to filter the result to any connected object.
	 *
	 * @param App_Taxonomy_Tag $tag
	 * @param string $map - restrict results to objects in this map domain, e.g. "community.project"
	 * @param string $direction - "forward" or "reverse"
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function connection_has_tag($tag, $map, $direction = 'forward', $verify = false) {
	
		// Query format, leave this here for debugging
		/* id in (
			SELECT c.id_a FROM _connections c, taxonomy_item_tag t 
			WHERE c.module_a = 'community.team' AND c.module_b = 'community.project' 

			AND t.map = CONCAT('community.project(', c.id_b, ')')
			AND t.tag = 15
		) */
	
		if(!$tag->id)
			return $this;
	
		// Allow both connection directions
		if($direction == 'forward') {
			$a = 'a'; $b = 'b';
		} else if($direction == 'reverse') {
			$a = 'b'; $b = 'a';
		} else {
			throw new Exception('Cannot call <code>List_MySQL->connection_has_tag($tag, $map, $direction = "forward", $verify = false)</code> with <code>$direction</code> anything other than <code>forward</code> or <code>reverse</code>');
		}
	
		$select = "SELECT `c`.`id_$a` FROM `_connections` `c`, `taxonomy_item_tag` `t`";
		
		$conn_cond = "`c`.`module_$a` = '$this->_map'";
		if($map)
			$conn_cond .= " AND `c`.`module_$b` = '$map'";
			
		$tag_cond = "`t`.`map` = CONCAT(`c`.`module_$b`, '(', `c`.`id_$b`, ')') AND `t`.`tag` = $tag->id";
		
		if($verify) return "`id` IN ($select WHERE $conn_cond AND $tag_cond)";
		$this->_query_cond[] = "`id` IN ($select WHERE $conn_cond AND $tag_cond)";
		return $this;
	}
	
	/**
	 * Automatic multiple field condition.
	 *
	 * @param string $condition
	 * @param string $fields
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function multiple_field_condition($condition, $fields, $verify = false) {
		$fields = explode(' ', $fields);
		if(count($fields) == 0)
			return $this;
		
		$query = '';
		foreach($fields as $field) {
			if(strtoupper($field) == 'OR') {
				$query .= ' OR ';
			} else if(strtoupper($field) == 'AND') {
				$query .= ' AND ';
			} else {
				$query .= "`$field` $condition";
			}
		}
		if($verify) return "($query)";
		$this->_query_cond[] = "($query)";
		return $this;
	}
	
	public function manual_condition($query) {
		$this->_query_cond[] = "($query)";
		return $this;		
	}
	
	/**
	 * Multiple field text search.
	 *
	 * @param string $term
	 * @param string $fields
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function multiple_field_search($term, $fields, $verify = false) {
		$term = mysql_real_escape_string($term);
		if(strlen($term) == 0)
			return $verify ? '' : $this;
		
		$like = '`' . implode('` LIKE "%'.$term.'%" OR `', explode(' ', $fields)). '` LIKE "%'.$term.'%"';
		$fields = '`' . implode('`,`', explode(' ', $fields)). '`';
		
		if($verify) return "($like OR MATCH($fields) AGAINST('$term'))";
		$this->_query_cond[] = "($like OR MATCH($fields) AGAINST('$term'))";
		
		return $this;
	}
	
	/**
	 * Add a sorting condition.
	 *
	 * @param string $field 
	 * @param string $dir
	 * @return $this
	 * @author David Boskovic
	 */
	public function order($field, $dir = 'ASC', $reset = false) {
		if($reset == 'reset' || $reset)
			$this->_order_cond = array();
		
		$field = ctype_alnum($field) ? "`$field`" : $field;
		if(!$field) return $this;
		$dir = ctype_alnum($dir) ? strtoupper($dir) : 'ASC';
		$this->_order_cond[] = "$field $dir";
		return $this;
		
	}
	
	/**
	 * Filter the results by their connections.
	 *
	 * @param string $module 
	 * @param string $direction 
	 * @param string $type 
	 * @return $this
	 * @author David Boskovic
	 */
	public function connections($module, $direction = CONN_DIR_INCOMING, $type = CONN_TYPE_ALL) {
		
		# perform a couple checks for integrity.
		if(!is_object($module)) die('Trying to access a list of connections without providing a module.');
		if(!$module->_map) die('Trying to access connections on a module that does not have a _map var set.');
		
		$t = $this->_table;
		$st = "`$t`.*";
			
		$this->_fields_select = $st;
		$this->_tables_select = "`_connections`, `$t`";
		
		if($direction == 'INCOMING') {		
			$conds = array(
				"`_connections`.`id_a`" => "`$t`.`id`",
				"`_connections`.`module_b`" => $module->_map,
				"`_connections`.`id_b`" => $module->id,
				"`_connections`.`module_a`" => $this->_map
			);
			if($type) $conds["`_connections`.`type`"] = $type;
		} else {	
			$conds = array(
				"`_connections`.`id_b`" => "`$t`.`id`",
				"`_connections`.`module_a`" => $module->_map,
				"`_connections`.`id_a`" => $module->id,
				"`_connections`.`module_b`" => $this->_map
			);
			if($type) $conds["`_connections`.`type`"] = $type;			
		}
		$this->_connection_conds = $conds;
		return $this;
	}
	

	/**
	 * Show results connected to a member. (this is a shortcut for IXML).
	 *
	 * @param string $member_id
	 * @return $this
	 * @author Nate Ferrero
	 */
	public function by_member($member_id) {
		$member = e::app('members')->account($member_id);
		if(!$member)
			return array();
		return $this->connections($member, CONN_DIR_OUTGOING);
	}
	
	/**
	 * Limit the results
	 *
	 * @param integer $start 
	 * @param integer $limit 
	 * @return $this
	 * @author David Boskovic
	 */
	public function limit($start,$limit = false) {
		
		if(!is_numeric($start) || !(is_numeric($limit) || $limit == false)) return $this;
		$this->_limit_size = $limit == false ? $start : $limit;
		$this->_limit = $limit == false ? "0, $start" : "$start, $limit";
		return $this;
		
	}
	
	/**
	 * Choose a specific page of results.
	 *
	 * @param string $page 
	 * @param string $length 
	 * @return integer
	 * @author David Boskovic
	 */
	public function page($page = 1, $length = false) {
		if($length) $this->_page_length = $length;
		$page = $page < 1 ? 1 : $page;
		$this->_on_page = $page;
		--$page;
		return $this->limit($page*$this->_page_length,$this->_page_length);
	}
	
	/**
	 * Set the page length
	 *
	 * @param string $length 
	 * @return integer
	 * @author David Boskovic
	 */
	public function page_length($length = false) {
		if($length) $this->_page_length = $length;
		return $this;
	}
	
	/**
	 * Get the information for the paging.
	 *
	 * @return array
	 * @author David Boskovic
	 */
	public function paging() {
		$pages = ceil($this->count('all') / $this->_page_length);
		$response = array('pages' => $pages, 'page' => $this->_on_page,'length' => $this->_page_length,'items' => $this->count('all'));
		return (object) $response;		
	}
	
	/**
	 * Get the paging HTML if a standard function is defined.
	 *
	 * @return html string
	 * @author Nate Ferrero
	 */
	public function paging_html($get_var = 'page', $size = 'default') {
		$paging = $this->paging();
		if(function_exists('draw_paginate'))
			return draw_paginate($paging->page,$paging->items,$paging->length, $get_var, $size);
		return 'Please define a function called <code>draw_paginate($page, $items, $page_length, $get_var)</code>.';
	}
	
	/**
	 * Get the total result count.
	 * This is also the implementation for count(List_MySQL) and subclasses.
	 *
	 * @return integer
	 * @author David Boskovic
	 */
	public function count($all = false, $fresh = false) {
		
		if($all == false && $this->_has_query != false) {
			return count($this->_results);
		} elseif($all == false) {
			if(!$this->_count) $this->_run_query('count');
			if($this->_limit_size !== false) {
				$c = $this->_count > $this->_limit_size ? $this->_limit_size : $this->_count;
			} else $c = $this->_count;
			return $c;
		} else {
			if(!$this->_count) $this->_run_query('count');
			return $this->_count;
		}
	}
	
	/**
	 * Get the number of items on the current page.
	 *
	 * @return integer
	 * @author Nate S. Ferrero
	 */
	public function current_page_count() {
		$paging = $this->paging();
		return max(0, min($paging->length, $paging->items - ($paging->page - 1) * $paging->length));
	}
	
	/**
	 * Run the actual query.
	 *
	 * @param string $count 
	 * @return void
	 * @author David Boskovic
	 */
	public function _run_query($count = false) {
		$cond = ' ';
		$con = (count($this->_connection_conds) > 0);
		if($con) {
			$cond .= 'WHERE (';
			$i = 0;
			foreach($this->_connection_conds as $key => $condi) {
				$condi = $this->condition($key, $condi, true);
				if(count($this->_connection_conds) > 1 && $i != 0)
					$cond .= '&& ';
				$cond .= $condi.' ';
				++$i;
			}
			$cond .= ') ';
		}
		if(count($this->_query_cond) > 0) {
			$cond .= $con ? 'AND (' : 'WHERE ';
			foreach($this->_query_cond as $key => $condi) {
				if(count($this->_query_cond) > 1 && $key != 0)
					$cond .= '&& ';
				$cond .= $condi.' ';
			}
			$cond .= $con ? ') ' :'';
		}
		if(!$count && count($this->_order_cond) > 0) {
			$cond .= 'ORDER BY ';
			foreach($this->_order_cond as $key => $condi) {
				if(count($this->_order_cond) > 1 && $key != 0)
					$cond .= ', ';
				$cond .= $condi.' ';
			}
		}
		if(!$count && $this->_limit) $cond .= 'LIMIT '.$this->_limit.' ';
		$fs = $this->_fields_select;
		if($count) $fs = "COUNT(*) AS `ct`";
		if(strpos($fs,'photo_bits') > -1) {
			//var_dump("SELECT $fs FROM $this->_tables_select $cond");die;
		}
		$results = e::$db->mysql->query("SELECT $fs FROM $this->_tables_select $cond");
		if($count) {
			$cr = $results->row();
			$this->_count = $cr['ct'];
			return true;
		}
		$pp = array();
		list($map_app, $map_module) = explode('.',$this->_map);
		while($row = $results->row()) {
			//var_dump($row);
			$pp[] = $this->component->$map_module($row);
		}
		$this->_results = $pp;
		$this->_has_query = true;
	}
	
	public function _scope_by_pos($pos) {
		return $this->_results[$pos];
	}
	public function _scope_rewind() {
		if($this->_has_query == false) $this->_run_query();
	}

	public function to_array() {
		$a = array();
		foreach($this as $item) {
			$a[] = $item;
		}
		return $a;
	}
	/**
	 * ITERATOR METHODS ----------------------------------------------------------------
	 */
	public function rewind() {
		if($this->_has_query == false) $this->_run_query();
		$this->position = 0;
	}

	public function current() {
		return $this->_results[$this->position];
	}

	public function key() {
		return $this->_results[$this->position]->id;
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		return isset($this->_results[$this->position]);
	}
}