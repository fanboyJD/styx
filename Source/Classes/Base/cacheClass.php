<?php
class Cache {
	private $prefix = 'framework_',
		$root = './Cache/',
		$engine = false,
		$engineInstance = null,
		$filecacheInstance = null,
		$cache = array();
	
	private static $instance;
	
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
			$this->root = Core::retrieve('basePath').$this->root;
		
		if($this->engine['type'] && Core::loadClass('Cache', $this->engine['type']))
			$this->engineInstance = new $this->engine['type']($this->prefix, $this->root);
		
		Core::loadClass('Cache', 'filecache');
		$this->filecacheInstance = new filecache($this->prefix, $this->root);
	}
	
	private function __clone(){}
	
	public static function getInstance($options = null){
		if(!self::$instance)
			self::$instance = new Cache($options);
		
		return self::$instance;
	}
	
	public function getEngine(){
		return $this->engine;
	}
	
	public function retrieve($key, $id, $ttl = null){
		if(!$this->cache[$key.'/'.$id]){
			if($this->engineInstance && $ttl!='file')
				$content = $this->engineInstance->retrieve($key.'/'.$id);
			else
				$content = $this->filecacheInstance->retrieve($key.'/'.$id);
			
			if(!$content) return null;
			
			$this->cache[$key.'/'.$id] = json_decode($content, true);
		}
		
		return $this->cache[$key.'/'.$id];
	}
	
	public function store($key, $id, $content, $ttl = 3600){
		if(!$content) return;
		$this->cache[$key.'/'.$id] = $content = Util::cleanWhitespaces($content);
		$content = json_encode($content);
		if($this->engineInstance && $ttl!='file')
			$this->engineInstance->store($key.'/'.$id, $content, $ttl);
		else
			$this->filecacheInstance->store($key.'/'.$id, $content, $ttl);
	}
	
	public function erase($key, $id, $force = false){
		unset($this->cache[$key.'/'.$id]);
		
		if($this->engineInstance)
			$this->engineInstance->erase($key.'/'.$id);
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase($key.'/'.$id, $force);
	}
	
	public function eraseBy($key, $id, $force = false){
		foreach($this->cache as $k => $v)
			if(Util::startsWith($k, $key.'/'.$id))
				unset($this->cache[$k]);
		
		if($this->engineInstance)
			$this->engineInstance->eraseBy($key.'/'.$id);

		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase($key.'/'.$id.'*', $force);
	}
	
	public function eraseAll($force = false){
		$this->cache = array();
		
		if($this->engineInstance)
			$this->engineInstance->eraseAll();
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase('*/*', $force);
	}
}
?>