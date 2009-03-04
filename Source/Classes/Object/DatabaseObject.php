<?php

abstract class DatabaseObject extends Object {
	
	protected $table;
	protected $criteria = array();
	
	public function __construct($data = null, $new = true){
		parent::__construct($data, $new);
		
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
		if(!$this->prepare()) return false;
		
		$identifier = $this->options['identifier']['internal'];
		
		if($this->new || empty($this->Data[$identifier])){
			$query = Database::insert($this->table);
		}else{
			$query = Database::update($this->table)->where(array($identifier => $this->Data[$identifier]));
			unset($this->Changed[$identifier]);
		}
		
		if(count($this->criteria)) $query->setCriteria($this->criteria);
		
		$this->Changed = Hash::remove($this->onSave($this->Changed), null);
		Hash::extend($this->Data, $this->Changed);
		$query->set($this->Changed)->query();
		
		if($this->new && empty($this->Data[$identifier]))
			$this->Data[$identifier] = Database::getInsertId();
		
		$this->new = false;
		$this->Changed = array();
		$this->modified = array();
		
		return true;
	}
	
	public function delete(){
		if($this->new) return parent::delete();
		
		$query = Database::delete($this->table);
		$identifier = $this->options['identifier']['internal'];
		
		if(count($this->criteria))
			$query->setCriteria($this->criteria);
		elseif(!empty($this->Data[$identifier]))
			$query->where(array($identifier => $this->Data[$identifier]));
		else // We can't delete just anything
			return $this;
		
		$query->query();
		
		return parent::delete();
	}
	
	public function getPagetitle($title, $options = array()){
		$contents = Database::select($this->table)
			->where($this->options['identifier']['external']." LIKE '".Data::pagetitle($title)."%'")
			->fields(array_unique(array_values($this->options['identifier'])))
			->retrieve();
		
		$options['contents'] = !empty($options['contents']) ? Hash::extend(Hash::splat($options['contents']), $contents) : $contents;
		
		return parent::getPagetitle($title, $options);
	}
	
}