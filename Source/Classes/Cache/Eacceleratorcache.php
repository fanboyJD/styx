<?php
/*
 * Styx::Eacceleratorcache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface for the eAccelerator-extension
 *
 */


class Eacceleratorcache {
	
	public $Configuration = array(
			'prefix' => null,
			'root' => null,
		);
	
	public function __construct($Configuration){
		$this->Configuration = $Configuration;
	}
	
	public function retrieve($key){
		return eaccelerator_get($this->Configuration['prefix'].$key);
	}
	
	public function store($key, $content, $ttl){
		eaccelerator_put($this->Configuration['prefix'].$key, $content, $ttl);
	}
	
	public function erase($key, $force = false){
		$key = $this->Configuration['prefix'].$key;
		
		eaccelerator_lock($key);
		eaccelerator_rm($key);
		eaccelerator_unlock($key);
	}
	
	public function eraseBy($key){
		$prefix = explode('/', $key);
		$keys = eaccelerator_list_keys();
		foreach($keys as $val)
			if(String::starts($val['name'], ':'.$this->Configuration['prefix'].$key))
				$this->erase($prefix[0].'/'.substr($val['name'], strrpos($val['name'], '/')+1));
	}
	
	public function eraseAll(){
		$keys = eaccelerator_list_keys();
		foreach($keys as $val)
			if(String::starts($val['name'], ':'.$this->Configuration['prefix']))
				$this->erase(substr($val['name'], strrpos($val['name'], $this->Configuration['prefix'])+strlen($this->Configuration['prefix'])));
	}
}