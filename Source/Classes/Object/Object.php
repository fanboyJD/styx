<?php

class Object implements Iterator, ArrayAccess, Countable {
	
	protected $Storage;
	protected $Changed;
	protected $name;
	protected $structure;
	protected $modified = array();
	protected $new = true;
	protected $options = array(
		'identifier' => null,
	);
	
	public function __construct($data = null, $new = true){
		$this->new = !!$new;
		$this->name = ucfirst(substr(get_class($this), 0, -6));
		
		$initialize = $this->initialize();
		
		if(isset($initialize['structure']) && is_array($initialize['structure'])){
			$this->structure = $initialize['structure'];
			unset($initialize['structure']);
			
			$this->clear();
			if($this->new)
				foreach($this->structure as $key => $value)
					$this->modified[$key] = true;
		}
		
		Hash::extend($this->options, Hash::splat($initialize));
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		
		if(is_array($data)) $this->store($data);
	}
	
	protected function initialize(){}
	protected function onSave($data){
		return $data;
	}
	
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
			
			$this->Changed[$key] = $this->Storage[$key] = Data::call($value, $this->structure[$key][':validate']);
		}
		
		return $this;
	}
	
	public function prepare(){
		if(!count($this->modified)) return false;
		
		$this->Changed = array_intersect_key($this->Storage, $this->modified);
		
		$this->validate()->sanitize();
		
		return true;
	}
	
	public function save(){
		if(!$this->prepare()) return $this;
		
		$this->Changed = $this->onSave($this->Changed);
		$this->new = false;
		$this->Changed = array();
		$this->modified = array();
		
		return $this;
	}
	
	public function delete(){
		$this->clear();
		
		return $this;
	}
	
	public function store($array, $value = null){
		if(!is_array($array))
			$array = array($array => $value);
		
		foreach($array as $key => $value){
			if($this->structure && !isset($this->structure[$key])) continue;
			
			$this->Storage[$key] = $value;
			$this->modified[$key] = true;
		}
		
		return $this;
	}
	
	public function retrieve($key, $value = null){
		if($value && empty($this->Storage[$key]))
			$this->store($key, $value);
		
		return !empty($this->Storage[$key]) ? $this->Storage[$key] : null;
	}
	
	public function clear(){
		$this->Storage = array();
		
		if($this->structure)
			foreach($this->structure as $key => $value)
				$this->Storage[$key] = null;
		
		return $this;
	}
	
	public function toArray(){
		return $this->Storage;
	}
	
	public function offsetExists($key){
		return isset($this->Storage[$key]);
	}
	
	public function offsetSet($key, $value){
		$this->store($key, $value);
	}
	
	public function offsetGet($key){
		return isset($this->Storage[$key]) ? $this->Storage[$key] : null;
	}
	
	public function offsetUnset($key){
		unset($this->Storage[$key]);
	}
	
	public function rewind(){
		reset($this->Storage);
	}
	
	public function current(){
		return current($this->Storage);
	}
	
	public function key(){
		return key($this->Storage);
	}
	
	public function next(){
		return next($this->Storage);
	}
	
	public function valid(){
		return !is_null($this->key());
	}
	
	public function reset(){
		return reset($this->Storage);
	}
	
	public function count(){
		return count($this->Storage);
	}
	
}