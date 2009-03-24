<?php

class Module {
	
	protected $Model;
	
	protected $options = array(
		/* Model / Object Options */
		/*'table' => null,*/
		'identifier' => null,
		'structure' => null,
		'objectname' => null,
		'cache' => true,
		
		/* Layer Options */
		/*'modelname' => null,*/
		'rebound' => true,
		'defaultEvent' => 'view',
		'defaultEditEvent' => 'edit',
		'preventPass' => array('post'), // Prevents passing post/get variable if the layer is not the Mainlayer
	);
	
	/**
	 * @return Module
	 */
	public static function create($module){
		return Core::classExists($module .= 'module') ? new $module : false;
	}
	
	/**
	 * @return Module
	 */
	public static function retrieve($layer){
		static $Instances;
		
		return empty($Instances[$module = strtolower($module)]) ? $Instances[$module] = Module::create($module) : $Instances[$module];
	}
	
	protected function __construct(){
		$this->name = ucfirst(substr(get_class($this), 0, -6));
		
		$initialize = $this->initialize();
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
		
		$this->objectname = !empty($this->options['objectname']) ? $this->options['objectname'] : strtolower($this->name).'object';
		$this->modelname = isset($this->options['modelname']) ? $this->options['modelname'] : strtolower($this->name);
		unset($this->options['modelname'], $this->options['objectname']);
	}
	
	public function getOptions(){
		return $this->options;
	}
	
	public function getName($class){
		switch(strtolower($class)){
			case 'object': return $this->objectname;
			case 'model': return $this->modelname;
			default: $this->name;
		};
	}
	
	public function getModel(){
		if(!$this->modelname) return null;
		
		return $this->Model ? $this->Model : $this->Model = Model::create($this->modelname);
	}
	
	public function getObjectName(){
		return substr($this->objectname, 0, -6);
	}
	
}