<?php
/*
 * Styx::Db - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles MySQL-Database connection
 *
 */

class db {
	
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
		
		if(is_array($options))
			$this->Configuration = $options;
		
		$this->Configuration['debug'] = Core::retrieve('debug');
	}
	
	private function __clone(){}
	
	public function __destruct(){
		$this->closeConnection();
	}
	
	/**
	 * @param array $options
	 * @return Db
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new db();
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
	public static function select($table, $cache = true){
		$class = 'Query'.($cache ? 'Cache' : 'Select');
		
		return new $class($table);
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public static function update($table){
		return new QueryHandler($table, 'update');
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public static function insert($table){
		return new QueryHandler($table, 'insert');
	}
	
	/**
	 * @param string $table
	 * @return QueryHandler
	 */
	public static function delete($table){
		return new QueryHandler($table, 'delete');
	}
	
	public function getQueries(){
		return $this->queries;
	}
	
	public function query($sql){
		if(!$this->isConnected){
			$this->connect();
			
			if(!$this->isConnected) die;
			
			$this->query("SET NAMES 'utf8'");
		}
		
		$query = mysql_query($sql);
		
		if($this->Configuration['debug']){
			$this->queries++;
			Script::log($sql);
			if(!$query) Script::log(mysql_error(), 'error');
		}
		
		return pick($query, false);
	}
	
	public function fetch($query, $type = null){
		if(!$query) return false;
		
		return pick(mysql_fetch_array($query, ($type ? $type : MYSQL_ASSOC)), false);
	}
	
	public function getId(){
		return mysql_insert_id();
	}
	
	public function numRows($query){
		return mysql_num_rows($query);
	}
	
	public function retrieve($sql){
		$query = $this->query($sql);
		
		if(!$query) return false;
		
		$rows = array();
		
		while($row = $this->fetch($query))
			$rows[] = $row;
		
		mysql_free_result($query);
		
		return Data::nullify($rows);
	}
	
}