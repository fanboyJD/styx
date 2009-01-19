<?php
/**
 * Styx::Memcachecache - Cache-Interface for memcached
 *
 * @package Styx
 * @subpackage Cache
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Memcachecache {
	
	/**
	 * The prefix to be used to not interfere with other Applications on the same server
	 *
	 * @var string
	 */
	private $prefix;
	/**
	 * An instance of the Memcache-Class
	 *
	 * @var Memcache
	 */
	private $memcache;
	
	/**
	 * Sets up the backend engine
	 *
	 * @param array $Configuration
	 */
	public function __construct($Configuration){
		$this->prefix = rtrim($Configuration['prefix'], '/').'/';
		
		$this->memcache = new Memcache;
		
		if(!Hash::length($Configuration['servers']))
			$Configuration['servers'] = array('host' => 'localhost');
		
		foreach($Configuration['servers'] as $server){
			$default = array(
				'host' => 'localhost',
				'port' => 11211,
				'persistent' => true,
				'weight' => 1,
				'timeout' => 15,
				'retryInterval' => 15,
				'status' => true,
				'callback' => null,
			);
			Hash::extend($default, $server);
			
			$this->memcache->addServer($default['host'], $default['port'], $default['persistent'], $default['weight'], $default['timeout'], $default['retryInterval'], $default['status'], $default['callback']);
		}
	}
	
	/**
	 * Fetches a value from the cache
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function retrieve($id){
		return $this->memcache->get($this->prefix.$id);
	}
	
	/**
	 * Stores a value in the cache
	 *
	 * @param string $id
	 * @param mixed $content
	 * @param int $ttl
	 */
	public function store($id, $content, $ttl = null){
		$this->memcache->set($this->prefix.$id, $content, false, $ttl);
	}
	
	/**
	 * Removes the given elements from the cache
	 *
	 * @param array $array
	 */
	public function erase($array){
		foreach($array as $id)
			$this->memcache->delete($this->prefix.$id);
	}
	
	/**
	 * Checks whether the extension is available or not
	 *
	 * @return bool
	 */
	public static function isAvailable(){
		return class_exists('Memcache', false);
	}
	
}