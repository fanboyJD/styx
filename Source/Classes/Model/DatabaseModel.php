<?php

abstract class DatabaseModel extends Model {
	
	protected $table;
	
	public function __construct(){
		parent::__construct();
		
		$this->table = !empty($this->options['table']) ? $this->options['table'] : strtolower($this->name);
	}
	
	public function setCache($cache){
		$this->cache = !!$cache;
		
		return $this;
	}
	
	public function setTable($table){
		$this->table = $table;
		
		return $this;
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function find($criteria){
		return $this->make($this->select()->setCriteria($criteria)->fetch());
	}
	
	public function findMany($criteria = array()){
		return $this->makeMany($this->select()->setCriteria($criteria)->retrieve());
	}
	
	public function select(){
		return Database::select($this->table, $this->options['cache']);
	}
	
	public function destroy($criteria){
		$this->Collection = array();
		
		Database::delete($this->table)->setCriteria($criteria)->query();
	}
	
}