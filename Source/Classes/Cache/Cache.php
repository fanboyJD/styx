<?php
/**
 * Styx::Cache - Caches data to different backends to make them persistent over requests and allow faster execution
 *
 * @package Styx
 * @subpackage Cache
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Cache {
	/**
	 * Configuration
	 *
	 * @var array
	 */
	private $Configuration = array(
			'default' => null,
			'engines' => array(),
			'prefix' => null,
			'root' => null,
			'servers' => array(), // For memcached
		);
	/**
	 * Holds all the stored variables
	 *
	 * @var array
	 */
	protected $Storage = array();
	/**
	 * The Metadata for all elements in the array
	 *
	 * @var array
	 */
	private	$Meta = array();
	/**
	 * Current Timestamp
	 *
	 * @var int
	 */
	private	$time = null;
	/**
	 * Holds the instances of all used engines
	 *
	 * @var array
	 */
	private	$engines = array();
	
	/**
	 * Sets up the caching engines and reads the metadata
	 *
	 */
	private function __construct(){
		Hash::extend($this->Configuration, Core::retrieve('cache'));
		
		if(!$this->Configuration['prefix']) $this->Configuration['prefix'] = Core::retrieve('prefix');
		
		if(!$this->Configuration['root']) $this->Configuration['root'] = Core::retrieve('path').'./Cache';
		$this->Configuration['root'] = realpath($this->Configuration['root']);
		
		$this->time = time();
		
		array_push($this->Configuration['engines'], 'file');
		
		$engines = array();
		foreach(glob(Core::retrieve('path').'Classes/Cache/*') as $file){
			$class = strtolower(substr(basename($file, '.php'), 0, -5));
			if(!in_array($class, $this->Configuration['engines']))
				continue;
			
			Core::loadClass('Cache', $class.'cache');
			if(call_user_func(array($class.'cache', 'isAvailable')))
				$engines[] = $class;
		}
		
		$default = strtolower($this->Configuration['default']);
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
	
	/**
	 * Saves the metadata list to the file-cache (as it is kind of "persistent")
	 *
	 */
	public function __destruct(){
		$this->engines['file']->store('Cache/List', json_encode($this->Meta));
	}
	
	/**
	 * Returns the Cache instance
	 *
	 * @return Cache
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Cache();
	}
	
	/**
	 * Returns the instance of a caching-interface so it can be used directly
	 *
	 * @return Apccache|Eacceleratorcache|Filecache|Memcache
	 */
	public static function getEngine($name = null){
		if($name) $name = strtolower($name);
		
		$c = self::getInstance();
		
		return $name && !empty($c->engines[$name]) ? $c->engines[$name] : $c->engines[$c->Configuration['default']];
	}
	
	/**
	 * Returns a list of the currently used engines
	 *
	 * @return array
	 */
	public function getEngines(){
		return array_keys($this->engines);
	}
	
	/**
	 * Returns a value by its id from the cache
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function retrieve($id){
		if(empty($this->Meta[$id])) return null;
		
		if(empty($this->Storage[$id])){
			$engine = pick($this->Meta[$id][2], 'file');
			
			$content = null;
			if(!empty($this->engines[$engine]))
				$content = $this->engines[$engine]->retrieve($id);
			
			if(!$content){
				unset($this->Meta[$id]);
				return null;
			}
			
			$this->Storage[$id] = $this->Meta[$id][1] ? json_decode($content, true) : $content;
		}
		
		return $this->Storage[$id];
	}
	
	/**
	 * Stores a value by id to the cache. Either stores to the given engine or the one cofigured
	 * as the default engine. If {@link $options} is a number it stores the value for the given time
	 * (in seconds). If this number is 0 it tries to store the data in the filecache and tries to keep
	 * it for unlimited time. If {@link $options} is an array you can specify the type (engine), the ttl,
	 * whether to encode the value or not (defaults to true) and it is possible to add tags
	 *
	 * @param string $id
	 * @param mixed $input
	 * @param int|array $options Either the lifetime as int or options as an array
	 * @return mixed
	 */
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
	
	/**
	 * Erases either one ore more elements from the cache given by there id
	 *
	 * @param string|array $array
	 * @param bool $force
	 * @return Cache
	 */
	public function erase($array, $force = false){
		if(!is_array($array))
			$array = array($array);
		
		$list = array();
		
		foreach($array as $id){
			if(empty($this->Meta[$id]))
				continue;
			
			if(!$this->Meta[$id][0] && !$force)
				continue;
			
			$engine = pick($this->Meta[$id][2], 'file');
			if(empty($list[$engine]))
				$list[$engine] = array();
			
			$list[$engine][] = $id;
			
			unset($this->Storage[$id], $this->Meta[$id]);
		}
		
		foreach($list as $k => $v)
			$this->engines[$k]->erase($v);
		
		return $this;
	}
	
	/**
	 * Erases all elements that start with the given string
	 *
	 * @param string $id
	 * @param bool $force
	 * @return Cache
	 */
	public function eraseBy($id, $force = false){
		$list = array();
		foreach($this->Meta as $k => $v)
			if(String::starts($k, $id))
				$list[] = $k;
			
		if(Hash::length($list))
			$this->erase($list, $force);
		
		return $this;
	}
	
	/**
	 * Erases all elements that are associated with the given tag
	 *
	 * @param string $tag
	 * @param bool $force
	 * @return Cache
	 */
	public function eraseByTag($tag, $force = false){
		$list = array();
		foreach($this->Meta as $k => $v)
			if(!empty($v[3]) && in_array($tag, $v[3]))
				$list[] = $k;
		
		if(Hash::length($list))
			$this->erase($list, $force);
		
		return $this;
	}
	
	/**
	 * Removes any cached variable
	 *
	 * @param bool $force
	 * @return Cache
	 */
	public function eraseAll($force = false){
		$this->erase(array_keys($this->Meta), $force);
		
		return $this;
	}
	
}