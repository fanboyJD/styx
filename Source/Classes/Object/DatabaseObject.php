<?php

class DatabaseObject extends Object {
	
	protected $table;
	protected $criteria = array();
	
	public function __construct($data = null){
		parent::__construct($data);
		
		$this->table = !empty($this->options['table']) ? $this->options['table'] : strtolower($this->name);
		unset($this->options['table']);
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function setCriteria($criteria){
		$this->criteria = $criteria;
	}
	
	public function save(){
		if(!$this->prepare()) return $this;
		
		$identifier = $this->options['identifier']['internal'];
		
		if($this->new || empty($this->Storage[$identifier])){
			$query = Database::insert($this->table);
		}else{
			$query = Database::update($this->table)->where(array($identifier => $this->Storage[$identifier]));
			unset($this->Changed[$identifier]);
		}
		
		if(count($this->criteria)) $query->setCriteria($this->criteria);
		
		$query->set($this->onSave($this->Changed))->query();
		
		if($this->new && empty($this->Storage[$identifier]))
			$this->Storage[$identifier] = Database::getInsertId();
		
		$this->new = false;
		$this->Changed = array();
		$this->modified = array();
		
		return $this;
	}
	
	public function delete(){
		$query = Database::delete($this->table);
		
		$identifier = $this->options['identifier']['internal'];
		
		if(count($this->criteria))
			$query->setCriteria($this->criteria);
		elseif(!empty($this->Storage[$identifier]))
			$query->where(array($identifier => $this->Storage[$identifier]));
		else // We can't delete just anything
			return $this;
		
		$query->query();
		
		return parent::delete();
	}
	
}