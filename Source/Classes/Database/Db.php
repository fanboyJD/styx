<?php
class db {
	private static $Instance;
	
	private
		$queries = 0,
		$Configuration = array(
			'host' => null,
			'user' => null,
			'password' => null,
			'db' => null,
		),
		$Connection = array(
			'c' => null,
			'db' => null,
		),
		$isConnected = false,
		$cache = array();
	
	private function __construct($options = null){
		if(is_array($options))
			$this->Configuration = $options;
		
		$this->Configuration['debugMode'] = Core::retrieve('debugMode');
	}
	
	private function __clone(){}
	
	public function __destruct(){
		$this->closeConnection();
	}
	
	/**
	 * @param array $options
	 * @return Db
	 */
	public static function getInstance($options = null){
		if(!self::$Instance)
			self::$Instance = new db($options);
		
		return self::$Instance;
	}
	
	public function connect(){
		$this->Connection['c'] = mysql_connect($this->Configuration['host'], $this->Configuration['user'], $this->Configuration['password']);
		$this->selectDatabase();
		if($this->Connection['c'])
			$this->isConnected = true;
		
		return $this->isConnected;
	}
	
	public function selectDatabase($db = null){
		if($db)
			$this->Configuration['db'] = $db;
		
		$this->Connection['db'] = mysql_select_db($this->Configuration['db']);
		return $this->Connection['db'];
	}
	
	public function closeConnection(){
		if($this->isConnected){
			$this->isConnected = false;
			mysql_close($this->Connection['c']);
		}
	}
	
	/**
	 * @param string $table
	 * @return QuerySelect
	 */
	public function select($table){
		return new QuerySelect($table);
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public function update($table){
		return new QueryHandler($table, 'update');
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public function insert($table){
		return new QueryHandler($table, 'insert');
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public function delete($table){
		return new QueryHandler($table, 'delete');
	}
	
	public function getQueries(){
		return $this->queries;
	}
	
	public function query($sql){
		if(!$this->isConnected){
			$this->connect();
			
			if(!$this->isConnected)
				die;
			
			$this->query("SET NAMES 'utf8'");
		}
		
		$query = mysql_query($sql);
		
		if($this->Configuration['debugMode']){
			$this->queries++;
			Script::log($sql);
			if(!$query)
				Script::log(mysql_error(), 'error');
		}
		
		return $query ? $query : false;
	}
	
	public function fetch($query, $type = null){
		if(!$query) return false;
		
		$row = mysql_fetch_array($query, ($type ? $type : MYSQL_ASSOC));
		return $row ? $row : false;
	}
	
	public function getId(){
		return mysql_insert_id();
	}
	
	public function numRows($query){
		return mysql_num_rows($query);
	}
	
	public function retrieve($sql){
		$query = $this->query($sql);
		
		while($row = $this->fetch($query))
			$rows[] = $row;
		
		mysql_free_result($query);
		return Data::nullify($rows);
	}
	
	public function store($sql, $key = 0){
		$this->cache[$key] = $this->query($sql);
	}
	
	public function next($key = 0){
		if(!$this->cache[$key])
			return false;
		
		$f = $this->fetch($this->cache[$key]);
		if(is_array($f))
			return Data::nullify($f);
		
		mysql_free_result($this->cache[$key]);
		unset($this->cache[$key]);
		return false;
	}
	
	//revise this method; escape strings; add the where stuff
	public function countAll($table = null, $where = null, $field = 'id'){
		if($table)
			$sql = 'SELECT COUNT('.$field.') FROM '.$table.' '.$where;
		else
			$sql = 'SELECT FOUND_ROWS()';
		
		$count = $this->fetch($this->query($sql), MYSQL_NUM);
		return $count[0] ? $count[0] : 0;
	}
}
?>