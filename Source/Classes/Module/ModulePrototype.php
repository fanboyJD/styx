<?php

class ModulePrototype {
	
	protected $Model;
	
	protected $structure;
	
	protected $options = array(
		/* Names */
		/*'model' => null,
		'object' => null,*/
		
		/* Model / Object Options */
		/*'table' => null,*/
		'identifier' => null,
		'cache' => true,
		
		/* Layer Options */
		'rebound' => true,
		'defaultEvent' => 'view',
		'defaultEditEvent' => 'edit',
		'preventPass' => array('post'), // Prevents passing post/get variable if the layer is not the Mainlayer
	);
	
	/**
	 * @return Module
	 */
	public static function create($name){
		return Core::classExists($module = $name.'module') ? new $module($name) : false;
	}
	
	/**
	 * @return Module
	 */
	public static function retrieve($module){
		static $Instances;
		
		return empty($Instances[$module = strtolower($module)]) ? $Instances[$module] = Module::create($module) : $Instances[$module];
	}
	
	protected function __construct($name){
		$this->name = ucfirst($name);
		
		Hash::extend($this->options, pick($this->onInitialize(), array()));
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		if(empty($this->options['object'])) $this->options['object'] = strtolower($this->name);
		if(!isset($this->options['model'])) $this->options['model'] = strtolower($this->name);
	}
	
	protected function onInitialize(){}
	protected function onStructureCreate(){}
	
	public function getName($class = null){
		switch(strtolower($class)){
			case 'object': return $this->options['object'];
			case 'model': return $this->options['model'];
			default: return strtolower($this->name);
		};
	}
	
	public function getOptions(){
		return $this->options;
	}
	
	public function getStructure(){
		return $this->structure ? $this->structure : $this->structure = pick($this->onStructureCreate(), array());
	}
	
	public function getModel(){
		if(empty($this->options['model'])) return null;
		
		return $this->Model ? $this->Model : $this->Model = Model::create($this->options['model']);
	}
	
}