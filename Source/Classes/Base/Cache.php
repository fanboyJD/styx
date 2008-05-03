<?php
class Cache extends DynamicStorage {
	private $prefix = 'framework_',
		$root = './Cache/',
		$engine = false,
		$engineInstance = null,
		$filecacheInstance = null;
	
	private static $Instance;
	
	private function __construct($options = array()){
		if((!$options['engine'] || $options['engine']=='eaccelerator') && function_exists('eaccelerator_get'))
			$this->engine = array(
				'type' => 'eaccelerator',
			);
		
		if($options['prefix'])
			$this->prefix = $options['prefix'];
		
		if($options['root'])
			$this->root = realpath($options['root']);
		else
			$this->root = Core::retrieve('path').$this->root;
		
		$class = $this->engine['type'].'cache';
		if($this->engine['type'] && Core::loadClass('Cache', $class))
			$this->engineInstance = new $class($this->prefix, $this->root);
		
		Core::loadClass('Cache', 'filecache');
		$this->filecacheInstance = new filecache($this->prefix, $this->root);
	}
	
	private function __clone(){}
	
	/**
	 * @param array $options
	 * @return Cache
	 */
	public static function getInstance($options = null){
		if(!self::$Instance)
			self::$Instance = new Cache($options);
		
		return self::$Instance;
	}
	
	public function getEngine(){
		return $this->engine;
	}
	
	public function retrieve($key, $id, $ttl = null){
		$content = parent::retrieve($key.'/'.$id);
		if(!$content){
			if($this->engineInstance && $ttl!='file')
				$content = $this->engineInstance->retrieve($key.'/'.$id);
			else
				$content = $this->filecacheInstance->retrieve($key.'/'.$id);
			
			if(!$content) return null;
			
			$content = json_decode($content, true);
			parent::store($key.'/'.$id, $content);
		}
		
		return $content;
	}
	
	public function store($key, $id, $input, $ttl = 3600){
		if(!$input) return;
		
		$content = Data::clean($input);
		parent::store($key.'/'.$id, $content);
		
		if($this->engineInstance && $ttl!='file')
			$this->engineInstance->store($key.'/'.$id, json_encode($content), $ttl);
		else
			$this->filecacheInstance->store($key.'/'.$id, json_encode($content), $ttl);
		
		return $input;
	}
	
	public function erase($key, $id, $force = false){
		parent::erase($key.'/'.$id);
		
		if($this->engineInstance)
			$this->engineInstance->erase($key.'/'.$id);
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase($key.'/'.$id, $force);
	}
	
	public function eraseBy($key, $id, $force = false){
		parent::eraseBy($key.'/'.$id);
		
		if($this->engineInstance)
			$this->engineInstance->eraseBy($key.'/'.$id);

		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase($key.'/'.$id.'*', $force);
	}
	
	public function eraseAll($force = false){
		parent::eraseAll();
		
		if($this->engineInstance)
			$this->engineInstance->eraseAll();
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase('*/*', $force);
	}
}
?>