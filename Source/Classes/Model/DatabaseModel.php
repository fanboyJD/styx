<?php

abstract class DatabaseModel extends Model {
	
	protected $table;
	
	public function __construct(){
		parent::__construct();
		
		$this->table = !empty($this->options['table']) ? $this->options['table'] : strtolower($this->name);
		unset($this->options['table']);
	}
	
	public function find($criteria){
		return parent::make($this->select()->setCriteria($criteria)->fetch());
	}
	
	public function findMany($criteria){
		return parent::makeMany($this->select()->setCriteria($criteria)->retrieve());
	}
	
	public function select(){
		return Database::select($this->table, $this->options['cache']);
	}
	
	public function destroy($criteria){
		$this->Collection = array();
		
		Database::delete($this->table)->setCriteria($criteria)->query();
	}
	
}