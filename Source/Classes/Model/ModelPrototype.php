<?php

abstract class ModelPrototype implements Iterator, Countable {
	
	protected $Collection = array();
	protected $name;
	protected $objectname;
	protected $options = array(
		'identifier' => null,
		'objectname' => null,
		'cache' => true,
	);
	
	/**
	 * @return Model
	 */
	public static function create($model){
		return Core::classExists($model .= 'model') && is_subclass_of($model, 'model') ? new $model() : false;
	}
	
	public function __construct(){
		$this->name = ucfirst(substr(get_class($this), 0, -5));
		
		$initialize = $this->initialize();
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		
		$this->objectname = !empty($this->options['objectname']) ? $this->options['objectname'] : strtolower($this->name).'object';
		unset($this->options['objectname']);
	}

	protected function initialize(){}
	
	public function createObject($data = null, $new = true){
		return new $this->objectname($data, $new);
	}
	
	public function getObjectname(){
		return substr($this->objectname, 0, -6);
	}
	
	public function getIdentifier($identifier = null){
		return $identifier && !empty($this->options['identifier'][$identifier]) ? $this->options['identifier'][$identifier] : $this->options['identifier'];
	}
	
	public function find($criteria){}
	public function findMany($criteria = array()){}
	
	public function findByIdentifier($data, $identifier = 'external'){
		if(empty($this->options['identifier'][$identifier])) $identifier = 'external';
		
		return $this->find(array(
			'where' => array(
				$this->options['identifier'][$identifier] => array($data, $this->options['identifier'][$identifier]),
			),
		));
	}
	
	public function createOrFindBy($data = null, $identifier = 'external'){
		$obj = $data ? $this->findByIdentifier($data, $identifier) : false;
		
		return $obj ? $obj : $this->createObject();
	}
	
	public function select(){}
	
	public function save(){
		$this->invoke('save');
	}
	
	public function delete(){
		$this->invoke('delete');
	}
	
	protected function invoke($method, $arg = null){
		if(!count($this->Collection)) return;
		
		foreach($this->Collection as $obj)
			if(isset($arg)) $obj->{$method}($arg);
			else $obj->{$method}();
	}
	
	public function make($array){
		if(!$array) return false;
		
		$this->Collection = array(new $this->objectname($array, false));
		return reset($this->Collection);
	}
	
	public function makeMany($list){
		$this->Collection = array();
		if(!count($list)) return false;
		
		foreach($list as $obj)
			if(is_array($obj))
				$this->Collection[] = new $this->objectname($obj, false);
		
		return count($this->Collection) ? $this->Collection : false;
	}
	
	public function rewind(){
		reset($this->Collection);
	}
	
	public function current(){
		return current($this->Collection);
	}
	
	public function key(){
		return key($this->Collection);
	}
	
	public function next(){
		return next($this->Collection);
	}
	
	public function valid(){
		return !is_null(key($this->Collection));
	}
	
	public function reset(){
		return reset($this->Collection);
	}
	
	public function count(){
		return count($this->Collection);
	}
	
}