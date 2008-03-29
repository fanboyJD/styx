<?php
class db {
	public $queries = 0;
	private static $instance;
	private
		$Configuration = array(
			'host' => null,
			'user' => null,
			'pwd' => null,
			'db' => null,
		),
		$Connection = array(
			'conn' => null,
			'db' => null,
		),
		$isConnected = false,
		$DEBUG = false,
		$handles = array();
	private function __construct(){}
	private function __clone(){}
	
	/* STATICS */
	public static function getInstance($conf = null){
		if(!self::$instance)
			self::$instance = new db();
		
		if($conf) self::$instance->Configuration = $conf;
		
		return self::$instance;
	}
	
	public static function add($string){
		return addslashes($string);
	}
	
	public static function addh($string){
		return trim(addslashes(htmlspecialchars($string)));
	}
	
	public static function strip($string){
		if(!$string)
			return '';
		
		if(is_array($string)){
			foreach($string as $key => $val)
				$string[$key] = self::strip($val);
			
			return $string;
		}
		return stripslashes($string);
	}
	
	public static function numericArray($array, $divider = 0){
		if(!is_array($array))
			return null;
		
		foreach($array as $key => $val)
			$array[$key] = is_array($val) ? self::numericArray($val, $divider) : self::numeric($val, $divider);
		
		return $array;
	}
	
	public static function numeric($int, $divider = 0){
		if(!is_numeric($int) || $int<0)
			return 0;
		
		if($divider){
			$remainder = $int/$divider;
			if(round($remainder)!=$remainder)
				$int -= $int%$divider;
		}
		
		return round($int);
	}
	
	public static function resetNull($data){
		if(is_array($data))
			foreach($data as $key=>$val)
				if(!$val && !(is_numeric($val) && $val==0))
					unset($data[$key]);
				elseif((is_numeric($val) && $val==0) || self::numeric($val))
					$data[$key] = self::numeric($val);
				elseif(is_array($val))
					$data[$key] = self::resetNull($val);
		
		return $data;
	}
	
	/*
		UPDATE and INSERT allow 3 input methods:
			$fields = (string) "myField='something', myOtherField=10"
			$fields = (string) "field1, field2, field3" and $data = array(val1, val2, val3)
			$fields = array(field=>value)
		The first two variants are just for compatibility
	*/
	public static function getInsertData($fields, $data){
		if(is_array($fields)){
			foreach($fields as $key => $val)
				$out[] = $key.'='.($val!==null ? "'".$val."'" : 'NULL');
		}elseif(is_array($data)){
			$fields = Util::cleanWhitespaces(explode(',', $fields));
			foreach($data as $key => $val)
				$out[] = $fields[$key].'='.($val!==null ? "'".$val."'" : 'NULL');
		}
		return ($out ? implode(', ', $out) : $fields);
	}
	
	/*
		GETSELECT AND SELECT allow 3 input methods:
			$data = (string) "WHERE id='15'"
			$data = array(id=>15, 'AND', uid=>array(1,2,3))
			$data = array(id=>array('operator'=>'!=', 15))
			$data = array('!id' => 5, 'OR', 'id' => 6) to avoid key overwriting
		For more complex data the first method should be used (like WHERE (id='15' AND uid=1) OR uid=15)
	*/
	public static function getWhereData($data){
		if(!is_array($data))
			return $data;
		
		$i = 0;
		foreach($data as $key => $val){
			if($key) $key = str_replace('!', '', $key);
			if(is_array($val)){
				if($val['operator']){
					foreach($val as $k => $v){
						if(db::numeric($k) || $k===0){
							$out[$i] = $key.$val['operator'].($v!==null ? "'".$v."'" : 'NULL');
							break;
						}
					}
				}elseif(is_array($val) && $key){
					$out[$i] = $key.' IN ('.implode(',', $val).')';
				}
			}else{
				$out[$i] = (!db::numeric($key) && $key!==0 ? $key.'='.($val!==null ? "'".$val."'" : 'NULL') : $val);
			}
			if(strpos($out[$i], 'LIMIT')!==false || strpos($out[$i], 'ORDER')!==false)
				$noWhere++;
			
			$i++;
		}
		return ($noWhere!=$i ? 'WHERE' : '').' '.implode(' ', $out);
	}
	
	public static function getSelectData($data){
		if(is_array($data))
			$data = implode(',', $data);
		
		return $data;
	}
	
	public static function countAll($table = null, $add = null){
		/* @var $db db */
		$db = db::getInstance();
		if($table)
			$sql = $db->getSelect($table, $add, 'COUNT(id)');
		else
			$sql = 'SELECT FOUND_ROWS()';
		$count = $db->fetch($db->query($sql), MYSQL_NUM);
		return $count[0];
	}
	
	/* INSTANCE METHODS */
	public function debug(){
		$this->DEBUG = ($this->DEBUG ? false : true);
	}
	
	public function connect(){
		$this->Connection['conn'] = mysql_connect($this->Configuration['host'], $this->Configuration['user'], $this->Configuration['pwd']);
		$this->selectDatabase();
		if($this->Connection['conn'])
			$this->isConnected = true;
		return $this->isConnected;
	}
	
	public function selectDatabase($db = null){
		if($db) $this->Configuration['db'] = $db;
		$this->Connection['db'] = mysql_select_db($this->Configuration['db']);
		return $this->Connection['db'];
	}
	
	public function closeDatabase(){
		if($this->isConnected){
			$this->isConnected = false;
			mysql_close($this->Connection['conn']);
		}
	}
	
	public function getSelect($table, $add = null, $select = '*'){
		return 'SELECT '.self::getSelectData($select).' FROM '.$table.' '.self::getWhereData($add);
	}
	
	public function select($table, $add = '', $select = '*', $type = null, $limit = true){
		$add = self::getWhereData($add);
		return self::resetNull($this->fetch($this->query($this->getSelect($table, $add.(strpos($add, 'LIMIT')===false && $limit ? ' LIMIT 0,1' : ''), self::getSelectData($select))), ($type ? $type : MYSQL_ASSOC)));
	}
	
	public function update($table, $add, $fields, $data = null, $limit = true){
		$add = self::getWhereData($add);
		return $this->query('UPDATE '.$table.' SET '.self::getInsertData($fields, $data).' '.$add.(strpos($add, 'LIMIT')===false && $limit ? ' LIMIT 1' : ''));
	}
	
	public function insert($table, $fields, $data = null){
		return $this->query('INSERT INTO '.$table.' SET '.self::getInsertData($fields, $data));
	}
	
	public function delete($table, $add, $limit = true){
		$add = self::getWhereData($add);
		return $this->query('DELETE FROM '.$table.' '.$add.(strpos($add, 'LIMIT')===false && $limit ? ' LIMIT 1' : ''));
	}
	
	public function query($sql){
		if(!$this->isConnected OR !$this->Connection['db']){
			$this->connect();
			if(!$this->isConnected)
				die;
			$this->query("SET NAMES 'utf8'");
		}
		$query = mysql_query($sql);
		if($this->DEBUG){
			$this->queries++;
			Script::log($sql);
			if(!$query)
				Script::log(mysql_error(), 'error');
		}
		
		return $query ? $query : false;
	}
	
	public function hquery($sql, $i = 0){
		$this->handles[$i] = $this->query($sql);
	}
	
	public function getId(){
		return mysql_insert_id();
	}
	
	public function numRows($query){
		return mysql_num_rows($query);
	}
	public function fetch($query, $type = null){
		$out = mysql_fetch_array($query, ($type ? $type : MYSQL_ASSOC));
		return $out;
	}
	
	public function next($i = 0){
		if(!$this->handles[$i])
			return false;
		
		$f = $this->fetch($this->handles[$i]);
		if(is_array($f))
			return self::resetNull($f);
		
		unset($this->handles[$i]);
		return false;
	}
	
	public function store($sql){
		$query = $this->query($sql);
		
		while($row = $this->fetch($query))
			$rows[] = $row;
		
		return self::resetNull($rows);
	}
}
?>