<?php
/*
 * Styx::Cache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Caches certain data (automatically) to an extension or to the harddisk
 *
 */


class Cache extends Storage {
	private $Configuration = array(
			'prefix' => null,
			'root' => './Cache/',
			'engine' => false,
		),
		$Meta = array(),
		$time = null,
		$cacheInstance = null,
		$persistentInstance = null;
	
	private function __construct(){
		$options = Core::retrieve('cache');
		
		if(empty($options['engine']) || $options['engine']=='eaccelerator')
			$this->Configuration['engine'] = 'eaccelerator';
		
		$this->Configuration['prefix'] = !empty($options['prefix']) ? $options['prefix'] : Core::retrieve('prefix');
		
		if(!empty($options['root'])) $this->Configuration['root'] = realpath($options['root']);
		else $this->Configuration['root'] = Core::retrieve('path').$this->Configuration['root'];
		
		$class = String::ucfirst($this->Configuration['engine']).'cache';
		if($this->Configuration['engine'] && Core::loadClass('Cache', $class))
			$this->cacheInstance = new $class($this->Configuration);
		
		Core::loadClass('Cache', 'Filecache');
		$this->persistentInstance = new Filecache($this->Configuration);
		
		$this->time = time();
		
		$this->Meta = json_decode($this->persistentInstance->retrieve('Cache/List'), true);
		
		if(!$this->Meta) $this->Meta = array();
		
		foreach($this->Meta as $k => $v)
			if($v[0] && $v[0]<$this->time)
				unset($this->Meta[$k]);
	}
	
	private function __clone(){}
	
	public function __destruct(){
		$this->persistentInstance->store('Cache/List', json_encode($this->Meta));
	}
	
	/**
	 * @param array $options
	 * @return Cache
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Cache();
	}
	
	public function getEngine(){
		return $this->Configuration['engine'];
	}
	
	public function retrieve($id){
		if(empty($this->Meta[$id])) return null;
		
		if(empty($this->Storage[$id])){
			$content = $this->{$this->cacheInstance && $this->Meta[$id][1] ? 'cacheInstance' : 'persistentInstance'}->retrieve($id);
			
			if(!$content){
				unset($this->Meta[$id]);
				return null;
			}
			
			$this->Storage[$id] = $this->Meta[$id][2] ? json_decode($content, true) : $content;
		}
		
		return $this->Storage[$id];
	}
	
	public function store($id, $input, $options = null){
		$default = array(
			'type' => 'cache',
			'ttl' => 3600,
			'encode' => true,
			'tags' => null,
		);
		
		if(is_numeric($options)) Hash::extend($default, array('ttl' => $options));
		elseif(is_array($options)) Hash::extend($default, $options);
		
		if(!$default['ttl']) $default['type'] = 'file';
		
		$this->Meta[$id] = array(
			$default['ttl'] ? $this->time+$default['ttl'] : 0,
			$default['type']=='cache' ? 1 : 0,
			$default['encode'] ? 1 : 0,
		);
		
		if(!empty($options['tags'])) $this->Meta[$id][] = array_values(Hash::splat($options['tags']));
		
		$content = $this->Meta[$id][2] ? json_encode($input) : $input;
		
		$this->{$this->cacheInstance && $this->Meta[$id][1] ? 'cacheInstance' : 'persistentInstance'}->store($id, $content, $default['ttl']);
		
		return $this->Storage[$id] = $input;
	}
	
	public function erase($array, $force = false){
		if(!is_array($array))
			$array = array($array);
		
		$list = array(
			'cacheInstance' => array(),
			'persistentInstance' => array(),
		);
		
		foreach($array as $id){
			if(empty($this->Meta[$id]))
				continue;
			
			if(!$this->Meta[$id][0] && !$force)
				continue;
			
			$list[$this->cacheInstance && $this->Meta[$id][1] ? 'cacheInstance' : 'persistentInstance'][] = $id;
			unset($this->Storage[$id], $this->Meta[$id]);
		}
		
		foreach($list as $k => $v)
			if(Hash::length($v))
				$this->{$k}->erase($v);
		
		return $this;
	}
	
	public function eraseBy($id, $force = false){
		$list = array();
		foreach($this->Meta as $k => $v)
			if(String::starts($k, $id))
				$list[] = $k;
			
		if(Hash::length($list))
			$this->erase($list, $force);
		
		return $this;
	}
	
	public function eraseByTag($tag, $force = false){
		$list = array();
		foreach($this->Meta as $k => $v)
			if(!empty($v[3]) && in_array($tag, $v[3]))
				$list[] = $k;
		
		if(Hash::length($list))
			$this->erase($list, $force);
		
		return $this;
	}
	
	public function eraseAll($force = false){
		$this->erase(array_keys($this->Meta), $force);
		
		return $this;
	}
	
}