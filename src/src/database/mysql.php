<?php

/**
 * The (mini) MySQL Wrapper
 * Written as a turbo
 *
 * @package mini
 * @author David D. Boskovic
 */
class Database_Mysql {
	
	/**
	 * Store the MySQL Connection Resource
	 *
	 * @var string
	 */
	private $connection;
	public static $query_count = 0;
	public function __construct() {
		$this->connect(e::$env['database']['host'],e::$env['database']['user'],e::$env['database']['pass'],e::$env['database']['database']);
	}
	
	public function connect($host, $user, $password, $database = false) {
		
			// Attempt to connect to the database.
			$this->connection = @mysql_connect($host, $user, $password);
			
			if(!$this->connection){
				throw new Exception(sprintf('Could not connect to database: %s:%s@%s/%s', $user,(MODE_DEVELOPMENT ? $password :'xxxxxxxx'), $host, $database));
			}

			// Select the correct database if one was specified.
			if($database != false) {

				if(!@mysql_select_db($database, $this->connection)) {
					throw new Exception('Could not select database: '.$database);
				}			}

			// Return the connection object.
			return $this->connection;
	}
	public static $qt = 0;
	public function query($sql, $vsprintf = false) {
		if(is_array($vsprintf)) $sql = vsprintf($sql, $vsprintf);
		elseif($vsprintf !== false) $sql = sprintf($sql, $vsprintf);
		$t = microtime(true);
		$result = mysql_query($sql, $this->connection) or $this->error($sql);
		self::$qt += (microtime(true) - $t) * 1000;
		//var_dump($sql, self::$qt);
		++self::$query_count;
		return new database_mysql_result($result,$sql);
	}
	
	private function error($sql){
		throw new Exception("<b>$sql</b> ---- ".mysql_error($this->connection). mysql_errno($this->connection));
	}
	public function select($table, $conditions='', $vsprintf = false) {
		return $this->query("SELECT * FROM `$table` $conditions", $vsprintf);
	}
	public function get_fields_as_keys($table) {
		$cols = $this->query("SHOW COLUMNS FROM ".$table)->result;
		$fields = array();
		
		if (mysql_num_rows($cols) > 0) {
   			while ($col = mysql_fetch_assoc($cols)) {
       				$fields[$col['Field']] = FALSE;
   			}				
			return $fields;				
		}
		else {
			return false;
		}
	}
	public function get_fields($table) {
		$cols = $this->query("SHOW COLUMNS FROM ".$table)->result;
		$fields = array();
		
		if (mysql_num_rows($cols) > 0) {
   			while ($col = mysql_fetch_assoc($cols)) {
       				$fields[$col['Field']] = $col;
   			}				
			return $fields;				
		}
		else {
			return false;
		}
	}
	public function count($table, $conditions, $vsprintf = false) {
		$query = $this->query("SELECT COUNT(*) AS ct FROM $table $conditions",$vsprintf);
		$ct = $query->row();
		return $ct['ct'];
	}
	public function insert($table, $array) {
		$insertfragment = $this->_insert_fragment($array);
		return $this->query("INSERT INTO $table SET $insertfragment");
	}
	public function replace($table, $array) {
		$insertfragment = $this->_insert_fragment($array);
		return $this->query("REPLACE INTO $table SET $insertfragment");
	}
	
	public function update($table, $array, $conditions = false) {
		$insertfragment = $this->_insert_fragment($array);
		return $this->query("UPDATE $table SET $insertfragment $conditions");
	}
	public function update_by_id($table, $id, $array) {
		return $this->update($table,$array, "WHERE `id` ='$id'");
	}
	public function select_by_id($table, $id) {
		return $this->select($table, 'WHERE `id` ='.$id)->row();
	}
	private function _insert_fragment($array) {
		$a = array();
		foreach($array as $column  => $value) {
			$a[] = "`$column` = '$value'";
		}
		return implode(', ',$a);
	}
}



class database_mysql_result {
	public $result;
	public $query;
	public function __construct($result,$query) {
		$this->result = $result;
		$this->query = $query;
	}
	public function sort($col, $dir = 'ASC') {
		$a = array();
		while($row = $this->row('assoc')) {
			$a[$row[$col]] = $row;
		}
		$this->rewind();
		if($dir == 'ASC') ksort($a);
		else krsort($a);
		return $a;
	}
	public function all($type = 'assoc', $callback = false) {
		$a = array();
		while($row = $this->row($type)) {
			if(is_callable($callback)) {
				$callback($row);
			}
			$a[] = $row;
		}
		if(count($a) > 0) $this->rewind();
		return $a;
	}
	public function rewind() {
		mysql_data_seek($this->result, 0);
	}
	public function row($type = 'assoc', $table = false) {
		if($this->result)
			switch($type) {
				case 'num' :
					return mysql_fetch_array($this->result, MYSQL_NUM);
				case 'model' :
					if(mysql_num_rows($this->result) == 0) return false;
					return e::helper('mysql_model', $table, mysql_fetch_assoc($this->result));
				case 'assoc' :
				default :
					return mysql_fetch_assoc($this->result);
			}
		else
			return false;
	}
	public function count() {
		return mysql_num_rows($this->object);
	}
}

