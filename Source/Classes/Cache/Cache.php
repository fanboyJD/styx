<?php
/*
 * Styx::Cache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Caches certain data (automatically) to an extension or to the harddisk
 *
 */


class Cache extends DynamicStorage {
	private $Configuration = array(
			'prefix' => null,
			'root' => './Cache/',
			'engine' => false,
		),
		$engineInstance = null,
		$filecacheInstance = null;
	
	private static $Instance;
	
	private function __construct(){
		$options = Core::retrieve('cache');
		
		if((!$options['engine'] || $options['engine']=='eaccelerator') && function_exists('eaccelerator_get'))
			$this->Configuration['engine'] = array(
				'type' => 'eaccelerator',
			);
		
		$this->Configuration['prefix'] = pick($options['prefix'], Core::retrieve('prefix'));
		
		if($options['root']) $this->Configuration['root'] = realpath($options['root']);
		else $this->Configuration['root'] = Core::retrieve('path').$this->Configuration['root'];
		
		$class = $this->Configuration['engine']['type'].'cache';
		if($this->Configuration['engine']['type'] && Core::loadClass('Cache', $class))
			$this->engineInstance = new $class($this->Configuration);
		
		Core::loadClass('Cache', 'Filecache');
		$this->filecacheInstance = new filecache($this->Configuration);
	}
	
	private function __clone(){}
	
	/**
	 * @param array $options
	 * @return Cache
	 */
	public static function getInstance(){
		if(!self::$Instance) self::$Instance = new Cache();
		
		return self::$Instance;
	}
	
	public function getEngine(){
		return $this->engine;
	}
	
	public function retrieve($key, $id, $ttl = null, $decode = true){
		$content = parent::retrieve($key.'/'.$id);
		if(!$content){
			$content = $this->{$this->engineInstance && $ttl!='file' ? 'engineInstance' : 'filecacheInstance'}->retrieve($key.'/'.$id);
			
			if(!$content) return null;
			
			if($decode) $content = json_decode($content, true);
			parent::store($key.'/'.$id, $content);
		}
		
		return $content;
	}
	
	public function store($key, $id, $input, $ttl = 3600, $encode = true){
		if(!$input) return;
		
		$content = $encode ? json_encode(Data::clean($input)) : $input;
		
		$this->{$this->engineInstance && $ttl!='file' ? 'engineInstance' : 'filecacheInstance'}->store($key.'/'.$id, $content, $ttl);
		
		return parent::store($key.'/'.$id, $input);;
	}
	
	public function erase($key, $id, $force = false){
		$name = $key.'/'.$id;
		parent::erase($name);
		
		if($this->engineInstance)
			$this->engineInstance->erase($name);
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->erase($name, $force);
	}
	
	public function eraseBy($key, $id, $force = false){
		$name = $key.'/'.$id;
		parent::eraseBy($name);
		
		if($this->engineInstance)
			$this->engineInstance->eraseBy($name);

		if($force || !$this->engineInstance)
			$this->filecacheInstance->eraseBy($name, $force);
	}
	
	public function eraseAll($force = false){
		parent::eraseAll();
		
		if($this->engineInstance)
			$this->engineInstance->eraseAll();
		
		if($force || !$this->engineInstance)
			$this->filecacheInstance->eraseAll($force);
	}
}