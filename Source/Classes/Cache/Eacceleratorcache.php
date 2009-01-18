<?php
/*
 * Styx::Eacceleratorcache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface for the eAccelerator-extension
 *
 */


class Eacceleratorcache {
	
	private $prefix;

	public function __construct($Configuration){
		$this->prefix = rtrim($Configuration['prefix'], '/').'/';
	}
	
	public function retrieve($id){
		return eaccelerator_get($this->prefix.$id);
	}
	
	public function store($id, $content, $ttl = null){
		eaccelerator_put($this->prefix.$id, $content, $ttl);
	}
	
	public function erase($array){
		foreach($array as $id)
			eaccelerator_rm($this->prefix.$id);
	}
	
	public function isAvailable(){
		return function_exists('eaccelerator_get');
	}
	
}