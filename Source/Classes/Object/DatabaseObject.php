<?php

abstract class DatabaseObject extends Object {
	
	public function __construct($data = null, $new = true){
		parent::__construct($data, $new);
		
		if(empty($this->options['table'])) $this->options['table'] = strtolower($this->name);
	}
	
	public function setTable($table){
		$this->options['table'] = $table;
		
		return $this;
	}
	
	public function getTable(){
		return $this->options['table'];
	}
	
	public function save(){
		if(!$this->prepare()) return false;
		
		$identifier = $this->options['identifier']['internal'];
		
		if($this->new || empty($this->Data[$identifier])){
			$query = Database::insert($this->options['table']);
		}else{
			$query = Database::update($this->options['table'])->where(array($identifier => $this->Data[$identifier]));
			unset($this->Changed[$identifier]);
		}
		
		if(count($this->criteria)) $query->setCriteria($this->criteria);
		
		$this->Changed = Hash::remove($this->onSave($this->new ? $this->onInsert($this->Changed) : $this->Changed), null);
		Hash::extend($this->Data, $this->Changed);
		$query->set($this->Changed)->query();
		
		if($this->new && empty($this->Data[$identifier]))
			$this->Data[$identifier] = Database::getInsertId();
		
		$this->onSaveComplete();
		$this->cleanup();
		
		return true;
	}
	
	public function delete(){
		if($this->new) return parent::delete();
		
		$query = Database::delete($this->options['table']);
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
		$contents = Database::select($this->options['table'])
			->where($this->options['identifier']['external']." LIKE '".Data::pagetitle($title)."%'")
			->fields(array_unique(array_values($this->options['identifier'])))
			->retrieve();
		
		$options['contents'] = !empty($options['contents']) ? Hash::extend(Hash::splat($options['contents']), $contents) : $contents;
		
		return parent::getPagetitle($title, $options);
	}
	
}