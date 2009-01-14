<?php
/*
 * Styx::Database - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles MySQL-Database connection
 *
 */

class Database {
	
	private $queries = 0,
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
		$isConnected = false;
	
	private function __construct(){
		$options = Core::retrieve('database');
		
		if(is_array($options)) $this->Configuration = $options;
	}
	
	private function __clone(){}
	
	public function __destruct(){
		$this->disconnect();
	}
	
	/**
	 * @param array $options
	 * @return Db
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Database();
	}
	
	public function connect(){
		$this->Connection['c'] = mysql_connect($this->Configuration['host'], $this->Configuration['user'], $this->Configuration['password']);
		$this->selectDatabase();
		
		if($this->Connection['c']) $this->isConnected = true;
		
		return $this->isConnected;
	}
	
	public function selectDatabase($db = null){
		if($db) $this->Configuration['db'] = $db;
		
		return $this->Connection['db'] = mysql_select_db($this->Configuration['db']);
	}
	
	public function disconnect(){
		if(!$this->isConnected)
			return;
		
		$this->isConnected = false;
		mysql_close($this->Connection['c']);
	}
	
	public function isConnected(){
		return $this->isConnected && !!$this->Connection['db'];
	}
	
	/**
	 * @param string $table
	 * @return QuerySelect
	 */
	public static function select($table, $cache = true){
		static $databasecache;
		if($databasecache===null) $databasecache = pick(Core::retrieve('database.cache'), false);
		
		$class = 'Query'.($cache && $databasecache ? 'Cache' : 'Select');
		
		return new $class($table);
	}
	
	/**
	 * @param string $table
	 * @return Query
	 */
	public static function update($table){
		return new Query($table, 'update');
	}
	
	/**
	 * @param string $table
	 * @return Query
	 */
	public static function insert($table){
		return new Query($table, 'insert');
	}
	
	/**
	 * @param string $table
	 * @return Query
	 */
	public static function delete($table){
		return new Query($table, 'delete');
	}
	
	public function getQueries(){
		return $this->queries;
	}
	
	public function query($sql){
		static $debug = null;
		
		if($debug===null) $debug = pick(Core::retrieve('debug'), false);
		
		if(!$this->isConnected){
			$this->connect();
			
			if(!$this->isConnected) die;
			
			$this->query("SET NAMES 'utf8'");
		}
		
		$query = mysql_query($sql, $this->Connection['c']);
		
		if($debug){
			$this->queries++;
			Script::log($sql);
			if(!$query) Script::log(mysql_error(), 'error');
		}
		
		return pick($query, false);
	}
	
	public function fetch($query, $type = null){
		return $query ? pick(mysql_fetch_array($query, ($type ? $type : MYSQL_ASSOC)), false) : false;
	}
	
	public function getId(){
		return mysql_insert_id();
	}
	
	public function retrieve($sql){
		$query = $this->query($sql);
		
		if(!$query) return false;
		
		$rows = array();
		while($row = mysql_fetch_array($query))
			$rows[] = $row;
		
		mysql_free_result($query);
		
		return $rows;
	}
	
}