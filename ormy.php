<?php

namespace ormy;

class Database {
	public $link;
	
	function __construct($host, $user, $password, $database_name){
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->database_name = $database_name;
		
		$this->is_open = false;
		$this->link = null;
		$this->select();
	}
	
	function __destruct(){
		$this->close();
	}
	
	function open(){
		$this->link = mysql_connect($this->host, $this->user, $this->password);
		if (!$this->link){
			die("Can't connect to database");
		}
		
		$this->is_open = true;
	}
	
	function select(){
		if (!$this->is_open) $this->open();
		
		if (!mysql_select_db($this->database_name))
			die("Can't select database");
	}
	
	function close(){
		$this->is_open = false;
		if ($this->link){
			mysql_close($this->link);
		}
	}
}

// library?
class Query {
	function __construct($sql, $database){
		$this->result = $database ? 
			mysql_query($sql, $database->link) 
			: mysql_query($sql);
			
		if (!$this->result) throw new \Exception(mysql_error());
	}

	function __destruct () {
		if (is_resource($this->result))
			mysql_free_result($this->result);
	}
	
	function fetch_row(){
		return mysql_fetch_array($this->result);
	}
	
	function get_inserted_id(){
		return mysql_insert_id();
	}
}

class Mapping {
	function __construct($database, $table, $columns, $classname, $fields){
		$this->database = $database;
		$this->table = $table;
		$this->columns = $columns;
		$this->classname = $classname;
		$this->fields = $fields;
		
		$this->id_column = $columns[0];
		$this->id_field = $fields[0];
	}
	
	function load($id){
		$columns_string = implode(", ", $this->columns);
		$q = new Query(
			"select $columns_string from $this->table where $this->id_field = $id", 
			$this->database);
		$row = $q->fetch_row();
		if (!$row) return null;
		
		$obj = new $this->classname();
		foreach($this->columns as $i => $column) {
			$fieldname = $this->fields[$i];
			$obj->$fieldname = $row[$i];
		}
		
		return $obj;
	}
	
	function create($obj){
		$columns_string = implode(", ", array_slice($this->columns, 1));
		
		$fields_to_insert = array_slice($this->fields, 1);
		$values = array();
		foreach ($fields_to_insert as $i => $value){
			$values[] = to_db_value($obj->$value, $this->database->link);
		}
		$vals_string = implode(", ", $values);
		
		$sql = <<<SQL
			insert into $this->table 
			($columns_string) 
			values ($vals_string)
SQL;
		$query = new Query($sql, $this->database);
		return $query->get_inserted_id();
	}
	
	function update($obj){
		$link = $this->database->link;
		$columns_n_values = implode(", ", 
			array_map(
				function($colname, $fieldname) use ($obj, $link) {
					$escaped_val = to_db_value($obj->$fieldname, $link);
					return "$colname = $escaped_val";
				},
				array_slice($this->columns, 1),
				array_slice($this->fields, 1)));
			
		$id_field = $this->id_field;
		$id = $obj->$id_field;
			
		$sql = <<<SQL
			update $this->table
			set $columns_n_values
			where $this->id_column = $id
SQL;
		new Query($sql, $this->database);
	}
	
	function delete($id){
		$sql = "delete from $this->table where $this->id_column = $id";
		$q = new Query($sql, $this->database);
	}

	function load_where($where_clause){
		$columns_string = implode(", ", $this->columns);
		$q = new Query("select $columns_string from $this->table where $where_clause", $this->database);
		
		$objs = array();
		
		while($row = $q->fetch_row()){
			if (!$row) return null;
			
			$obj = new $this->classname();
			foreach($this->columns as $i => $column) {
				$fieldname = $this->fields[$i];
				$obj->$fieldname = $row[$i];
			}
			$objs[] = $obj;
		}
		
		return $objs;
	}
}

function to_db_value($var, $link){
	if (is_null($var)) {
		return "NULL";
	}
	else if (is_string($var)) {
		$escaped = mysql_real_escape_string($var, $link);
		return "'$escaped'";
	}
	else if (is_bool($var)) {
		return $var ? 1 : 0;
	}
	else if (is_numeric($var)) {
		return 0 + $var;
	}
	else {
		$t = gettype($var);
		throw new Exception("Unsupported type: $t");
	}
}

// probably replace this with PHP's built in array_map
function map_array($arr, $fn) {
	$result = array();
	foreach($arr as $k => $v) {
		$result[$k] = $fn($v);
	}
	return $result;
}

function annotate_columns($table, $columns){
	return map_array($columns, 
		function($col) { return "$table.$col"; });
}

//UNTESTED
class MultiMapping {
	function __construct($link_table, $link_columns, 
						$target_table, $target_columns,
						$classname, $fields){
		$this->link_table = $link_table;
		$this->link_columns = annotate_columns($link_table, $link_columns);
		$this->target_table = $target_table;
		$this->target_columns = annotate_columns($target_table, $target_columns);
		$this->classname = $classname;
		$this->fields = $fields;
		
		$this->link_id = $link_columns[0];
		$this->link_target_ref = $link_columns[1];
		$this->target_id = $target_columns[0];
	}
	
	function load($link_id){

		$target_colstring = implode(", ", $this->target_columns);
		$query_sql = <<<SQL
			select $target_colstring 
			from $this->link_table, $this_target_table 
			where $this->target_id = $this->link_target_ref
			and $this->link_id = $link_id
SQL;
		$q = new Query($query_sql);
		
		$objs = array();
		
		while($row = $q->fetch_row()){
			if (!$row) return null;
			
			$obj = new $this->classname();
			foreach($this->columns as $i => $column) {
				$fieldname = $this->fields[$i];
				$obj->$fieldname = $row[$i];
			}
			$objs[] = $obj;
		}
		
		return $objs;
	}
	
	function save_link($link_id, $target_obj){
	}
	
	function delete_link($link_id, $target_obj){
	}
}

?>