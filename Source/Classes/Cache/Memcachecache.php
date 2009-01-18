<?php
/*
 * Styx::Memcachecache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface to memcached
 *
 */


class Memcachecache {
	
	private $prefix;
	private $memcache;

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
	
	public function retrieve($id){
		return $this->memcache->get($this->prefix.$id);
	}
	
	public function store($id, $content, $ttl = null){
		$this->memcache->set($this->prefix.$id, $content, false, $ttl);
	}
	
	public function erase($array){
		foreach($array as $id)
			$this->memcache->delete($this->prefix.$id);
	}
	
	public function isAvailable(){
		return class_exists('Memcache', false);
	}
	
}