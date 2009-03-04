<?php

abstract class Object implements Iterator, ArrayAccess, Countable {
	
	protected $Data;
	protected $Changed;
	protected $Form;
	protected $name;
	protected $structure;
	protected $modified = array();
	protected $new = true;
	protected $options = array(
		'identifier' => null,
	);
	
	public function __construct($data = null, $new = true){
		$this->name = ucfirst(substr(get_class($this), 0, -6));
		$this->new = !!$new;
		
		$initialize = $this->initialize();
		
		if(isset($initialize['structure']) && is_array($initialize['structure'])){
			$this->structure = $initialize['structure'];
			unset($initialize['structure']);
			
			$this->clear();
			if($this->new)
				foreach($this->structure as $key => $value){
					$this->modified[$key] = true;
					if(!empty($value[':default'])) $this->Data[$key] = $value[':default'];
				}
		}
		
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		
		if(is_array($data)){
			// If it is loaded from a datasource it should not care about the :public modifier
			if($this->structure && !$this->new){
				foreach($this->structure as $key => $value)
					if(!empty($data[$key]))
						$this->Data[$key] = $data[$key];
			}else{
				$this->store($data);
			}
		}
	}
	
	protected function initialize(){}
	protected function onSave($data){
		return $data;
	}
	protected function onDelete(){}
	protected function onFormCreate(){}
	
	public function validate(){
		foreach($this->Changed as $key => $value){
			if(empty($this->structure[$key][':validate']))
				continue;
			
			if(($v = Validator::call($value, $this->structure[$key][':validate']))!==true)
				throw new ValidatorException($v, !empty($this->structure[$key][':caption']) ? $this->structure[$key][':caption'] : $key);
		}
		
		return $this;
	}
	
	public function sanitize(){
		foreach($this->Changed as $key => $value){
			if(empty($this->structure[$key][':validate']))
				continue;
			
			$this->Changed[$key] = $this->Data[$key] = Data::call($value, $this->structure[$key][':validate']);
		}
		
		return $this;
	}
	
	public function prepare(){
		if(!count($this->modified)) return false;
		
		$this->Changed = array_intersect_key($this->Data, $this->modified);
		
		$this->validate()->sanitize();
		
		return true;
	}
	
	public function save(){
		if(!$this->prepare()) return false;
		
		$this->Changed = $this->onSave($this->Changed);
		$this->new = false;
		$this->Changed = array();
		$this->modified = array();
		
		return true;
	}
	
	public function delete(){
		if(!$this->new) $this->onDelete();
		$this->clear();
		
		return $this;
	}
	
	public function store($array, $value = null){
		if(!is_array($array)) $array = array($array => $value);
		
		foreach($array as $key => $value){
			if(($this->structure && !isset($this->structure[$key])) || empty($this->structure[$key][':public'])) continue;
			
			$this->Data[$key] = $value;
			$this->modified[$key] = true;
		}
		
		return $this;
	}
	
	public function retrieve($key, $value = null){
		if($value && empty($this->Data[$key]))
			$this->store($key, $value);
		
		return !empty($this->Data[$key]) ? $this->Data[$key] : null;
	}
	
	public function getIdentifier($identifier = 'external'){
		return $this->retrieve(empty($this->options['identifier'][$identifier]) ? 'external' : $identifier);
	}
	
	public function clear(){
		$this->Data = array();
		
		if($this->structure)
			foreach($this->structure as $key => $value)
				$this->Data[$key] = null;
		
		return $this;
	}
	
	public function getPagetitle($title, $options = array()){
		$options['identifier'] = $this->options['identifier'];
		$identifier = $this->options['identifier']['internal'];
		if(!$this->new && !empty($this->Data[$identifier])){
			$options['id'] = $this->Data[$identifier];
			if(!empty($this->structure[$identifier][':validate']))
				$options['id'] = Data::call($options['id'], $this->structure[$identifier][':validate']);
		}
		
		return Data::pagetitle($title, $options);
	}
	
	public function createForm(){
		if($this->Form) return $this->Form;
		
		if(!$this->structure) return;
		
		$this->Form = new Form;
		foreach($this->structure as $key => $value){
			if(empty($value[':public']))
				continue;
			
			if(!empty($this->Data[$key])) $value['value'] = $this->Data[$key];
			$value['name'] = $key;
			$class = !empty($value[':element']) ? $value[':element'] : 'Input';
			$this->Form->addElement(new $class($value));
		}
		$this->onFormCreate();
		
		return $this->Form;
	}
	
	public function isNew(){
		return $this->new;
	}
	
	public function toArray(){
		return $this->Data;
	}
	
	public function offsetExists($key){
		return isset($this->Data[$key]);
	}
	
	public function offsetSet($key, $value){
		$this->store($key, $value);
	}
	
	public function offsetGet($key){
		return isset($this->Data[$key]) ? $this->Data[$key] : null;
	}
	
	public function offsetUnset($key){
		unset($this->Data[$key]);
	}
	
	public function rewind(){
		reset($this->Data);
	}
	
	public function current(){
		return current($this->Data);
	}
	
	public function key(){
		return key($this->Data);
	}
	
	public function next(){
		return next($this->Data);
	}
	
	public function valid(){
		return !is_null(key($this->Data));
	}
	
	public function reset(){
		return reset($this->Data);
	}
	
	public function count(){
		return count($this->Data);
	}
	
}