<?php

abstract class DatabaseModel extends Model {
	
	protected $table;
	
	public function __construct(){
		parent::__construct();
		
		$this->table = !empty($this->options['table']) ? $this->options['table'] : strtolower($this->name);
		unset($this->options['table']);
	}
	
	public function find($criteria){
		return parent::make(Database::select($this->table)->setCriteria($criteria)->fetch());
	}
	
	public function findMany($criteria){
		return parent::makeMany(Database::select($this->table)->setCriteria($criteria)->retrieve());
	}
	
}