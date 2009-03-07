<?php

abstract class ObjectPrototype implements Iterator, ArrayAccess, Countable {
	
	protected $Data = array();
	protected $Changed = array();
	protected $Garbage = array();
	protected $Form;
	protected $name;
	protected $structure;
	protected $criteria = array();
	protected $requireSession = false;
	protected $modified = array();
	protected $new = true;
	protected $options = array(
		'identifier' => null,
	);
	
	public function __construct($data = null, $new = true){
		$this->name = ucfirst(substr(get_class($this), 0, -6));
		$this->new = !!$new;
		
		$initialize = $this->initialize();
		
		if(isset($initialize['structure'])){
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
		
		if(is_object($data)) $data = $data->toArray();
		if(is_array($data)){
			// If it is loaded from a datasource it should not care about the :public modifier
			if($this->structure && !$this->new){
				foreach($this->structure as $key => $value)
					if(!empty($data[$key])){
						$this->Data[$key] = $data[$key];
						unset($data[$key]);
					}
				
				if(count($data)) $this->Garbage = $data;
			}else{
				$this->store($data);
			}
		}
	}
	
	protected function initialize(){}
	protected function onSave($data){ return $data; }
	protected function onDelete(){}
	protected function onFormCreate(){}
	
	public function setCriteria($criteria){
		$this->criteria = $criteria;
	}
	
	protected function validate(){
		foreach($this->Changed as $key => $value)
			if(!empty($this->structure[$key][':validate']))
				if(($v = Validator::call($value, $this->structure[$key][':validate']))!==true)
					throw new ValidatorException($v, !empty($this->structure[$key][':caption']) ? $this->structure[$key][':caption'] : $key);
		
		return $this;
	}
	
	protected function sanitize(){
		foreach($this->Changed as $key => $value)
			if(!empty($this->structure[$key][':validate']))
				 $this->Changed[$key] = $this->Data[$key] = Data::call($value, $this->structure[$key][':validate']);
		
		return $this;
	}
	
	public function prepare(){
		if(!count($this->modified)) return false;
		
		$this->checkSession();
		$this->Changed = array_intersect_key($this->Data, $this->modified);
		$this->validate()->sanitize();
		
		return true;
	}
	
	public function save(){
		if(!$this->prepare()) return false;
		
		$this->Changed = $this->onSave($this->Changed);
		$this->cleanup();
		
		return true;
	}
	
	public function delete(){
		if(!$this->new) $this->onDelete();
		$this->clear();
		
		return $this;
	}
	
	public function store($array, $value = null){
		if(is_scalar($array)) $array = array($array => $value);
		
		foreach($array as $key => $value){
			if($this->structure && (!isset($this->structure[$key]) || empty($this->structure[$key][':public']))){
				$this->Garbage[$key] = $value;
				continue;
			}
			
			$this->Data[$key] = $value;
			$this->modified[$key] = true;
		}
		
		return $this;
	}
	
	protected function modify($array, $value = null){
		if(is_scalar($array)) $array = array($array => $value);
		
		foreach($array as $key => $value){
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
	
	public function clear(){
		$this->Data = array();
		
		if($this->structure)
			foreach($this->structure as $key => $value)
				$this->Data[$key] = !empty($value[':default']) ? $value[':default'] : null;
			
		return $this;
	}
	
	protected function cleanup(){
		$this->new = false;
		$this->Changed = array();
		$this->modified = array();
	}
	
	public function getIdentifier($identifier = 'external'){
		return $this->retrieve(empty($this->options['identifier'][$identifier]) ? $this->options['identifier']['external'] : $this->options['identifier'][$identifier]);
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
	
	public function getForm(){
		if(!$this->structure) return false;
		
		if($this->Form) return $this->Form;
		
		$this->Form = new FormElement;
		foreach($this->structure as $key => $value){
			if(empty($value[':public']))
				continue;
			
			if(!empty($this->Data[$key])) $value['value'] = $this->Data[$key];
			$value['name'] = $key;
			$class = (!empty($value[':element']) ? $value[':element'] : 'Input').'Element';
			$this->Form->addElement(new $class($value));
		}
		$this->onFormCreate();
		
		if($this->requireSession){
			$config = Core::retrieve('user');
			$this->Form->addElement(new HiddenElement(array(
				'name' => Core::generateSessionName($this->name),
				'value' => User::get($config['session']),
			)));
		}
		
		return $this->Form;
	}
	
	public function requireSession(){
		$this->requireSession = true;
		
		return $this;
	}
	
	public function checkSession($container = null){
		if(!$this->requireSession) return $this;
		
		$session = Core::generateSessionName($this->name);
		if(!$container) $container = $this->Garbage;
		if(empty($container[$session]) || !User::checkSession($container[$session]))
			throw new ValidatorException('session');
		
		return $this;
	}
	
	public function isNew(){
		return $this->new;
	}
	
	public function toArray(){
		return $this->Data;
	}
	
	public function offsetSet($key, $value){
		$this->store($key, $value);
	}
	
	public function offsetGet($key){
		return isset($this->Data[$key]) ? $this->Data[$key] : null;
	}
	
	public function offsetExists($key){
		return isset($this->Data[$key]);
	}
	
	public function offsetUnset($key){
		if($this->structure && (!isset($this->structure[$key]) || empty($this->structure[$key][':public'])))
			return;
		
		$this->Data[$key] = null;
		$this->modified[$key] = true;
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