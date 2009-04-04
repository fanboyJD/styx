<?php

abstract class DatabaseModel extends Model {
	
	public function __construct(){
		parent::__construct();
		
		if(empty($this->options['table'])) $this->options['table'] = strtolower($this->name);
	}
	
	public function setCache($cache){
		$this->options['cache'] = !!$cache;
		
		return $this;
	}
	
	public function setTable($table){
		$this->options['table'] = $table;
		
		return $this;
	}
	
	public function getTable(){
		return $this->options['table'];
	}
	
	public function find($criteria){
		return $this->make($this->select()->setCriteria($criteria)->fetch());
	}
	
	public function findMany($criteria = array()){
		return $this->makeMany($this->select()->setCriteria($criteria)->retrieve());
	}
	
	public function select(){
		return Database::select($this->options['table'], $this->options['cache']);
	}
	
	public function destroy($criteria){
		$this->Collection = array();
		
		Database::delete($this->options['table'])->setCriteria($criteria)->query();
	}
	
}