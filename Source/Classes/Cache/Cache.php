<?php
/*
 * Styx::Cache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Caches certain data (automatically) to an extension or to the harddisk
 *
 */


class Cache {
	/**
	 * Holds all the stored variables
	 *
	 * @var array
	 */
	protected $Storage = array();
	private $Configuration = array(
			'default' => null,
			'engines' => array(),
			'prefix' => null,
			'root' => null,
			'servers' => array(), // For memcached
		),
		$Meta = array(),
		$time = null,
		$engines = array();
	
	private function __construct(){
		Hash::extend($this->Configuration, Core::retrieve('cache'));
		
		if(!$this->Configuration['prefix']) $this->Configuration['prefix'] = Core::retrieve('prefix');
		
		if(!$this->Configuration['root']) $this->Configuration['root'] = Core::retrieve('path').'./Cache';
		$this->Configuration['root'] = realpath($this->Configuration['root']);
		
		$this->time = time();
		
		array_push($this->Configuration['engines'], 'file');
		
		$engines = array();
		foreach(glob(Core::retrieve('path').'Classes/Cache/*') as $file){
			$class = String::toLower(String::sub(basename($file, '.php'), 0, -5));
			if(!in_array($class, $this->Configuration['engines']))
				continue;
			
			Core::loadClass('Cache', $class.'cache');
			if(call_user_func(array($class.'cache', 'isAvailable')))
				$engines[] = $class;
		}
		
		$default = String::toLower($this->Configuration['default']);
		if(!$default || !in_array($default, $engines))
			$this->Configuration['default'] = reset($engines);
		
		$this->Configuration['engines'] = array();
		foreach($engines as $engine){
			$this->Configuration['engines'][] = $engine;
			
			$class = $engine.'cache';
			$this->engines[$engine] = new $class($this->Configuration);
		}
		
		$this->Meta = pick(json_decode($this->engines['file']->retrieve('Cache/List'), true), array());
		
		foreach($this->Meta as $k => $v)
			if($v[0] && $v[0]<$this->time)
				unset($this->Meta[$k]);
	}
	
	private function __clone(){}
	
	public function __destruct(){
		$this->engines['file']->store('Cache/List', json_encode($this->Meta));
	}
	
	/**
	 * @param array $options
	 * @return Cache
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Cache();
	}
	
	public function getEngines(){
		return array_keys($this->engines);
	}
	
	public function retrieve($id){
		if(empty($this->Meta[$id])) return null;
		
		if(empty($this->Storage[$id])){
			$content = $this->engines[pick($this->Meta[$id][2], 'file')]->retrieve($id);
			
			if(!$content){
				unset($this->Meta[$id]);
				return null;
			}
			
			$this->Storage[$id] = $this->Meta[$id][1] ? json_decode($content, true) : $content;
		}
		
		return $this->Storage[$id];
	}
	
	public function store($id, $input, $options = null){
		$default = array(
			'type' => null,
			'ttl' => 3600,
			'encode' => true,
			'tags' => null,
		);
		
		if(is_numeric($options)) Hash::extend($default, array('ttl' => $options));
		elseif(is_array($options)) Hash::extend($default, $options);
		
		if(!$default['ttl']) $default['type'] = 'file';
		elseif(empty($this->engines[$default['type']])) $default['type'] = $this->Configuration['default'];
		
		$this->Meta[$id] = array(
			$default['ttl'] ? $this->time+$default['ttl'] : 0,
			$default['encode'] ? 1 : 0,
			$default['type']=='file' ? 0 : $default['type'],
		);
		
		if(!empty($options['tags'])) $this->Meta[$id][] = array_values(Hash::splat($options['tags']));
		
		$this->engines[pick($this->Meta[$id][2], 'file')]->store($id, $this->Meta[$id][1] ? json_encode($input) : $input, $default['ttl']);
		
		return $this->Storage[$id] = $input;
	}
	
	public function erase($array, $force = false){
		if(!is_array($array))
			$array = array($array);
		
		$list = array();
		
		foreach($array as $id){
			if(empty($this->Meta[$id]))
				continue;
			
			if(!$this->Meta[$id][0] && !$force)
				continue;
			
			if(empty($list[$this->Meta[$id][2]]))
				$list[$this->Meta[$id][2]] = array();
			
			$list[$this->Meta[$id][2]][] = $id;
			
			unset($this->Storage[$id], $this->Meta[$id]);
		}
		
		foreach($list as $k => $v)
			$this->engines[$k]->erase($v);
		
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