<?php

abstract class Model {
	
	protected $Collection = array();
	protected $name;
	protected $objectname;
	protected $options = array(
		'identifier' => null,
	);
	
	/**
	 * @return Model
	 */
	public static function create($model){
		if(!Core::autoload($model .= 'model'))
			return false;
		
		return is_subclass_of($model, 'model') ? new $model() : false;
	}
	
	public function __construct(){
		$this->name = ucfirst(substr(get_class($this), 0, -5));
		
		$initialize = $this->initialize();
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		
		$this->objectname = !empty($this->options['objectname']) ? $this->options['objectname'] : strtolower($this->name).'object';
		unset($this->options['objectname']);
	}
	
	public function getObjectname(){
		return $this->objectname;
	}
	
	protected function initialize(){}
	
	public function find($criteria){}
	public function findMany($criteria){}
	
	public function findByIdentifier($data, $identifier = 'external'){
		if(empty($this->options['identifier'][$identifier])) $identifier = 'external';
		
		return $this->find(array(
			'where' => array(
				$this->options['identifier'][$identifier] => array($data, $this->options['identifier'][$identifier]),
			),
		));
	}
	
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
		$this->Collection = array(new $this->objectname($array, false));
		return reset($this->Collection);
	}
	
	public function makeMany($list){
		$this->Collection = array();
		
		foreach($array as $obj)
			$this->Collection[] = new $this->objectname($obj, false);
		
		return $this->Collection;
	}
	
}