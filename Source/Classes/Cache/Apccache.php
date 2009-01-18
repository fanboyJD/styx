<?php
/*
 * Styx::Apccache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface for the apc-extension
 *
 */


class Apccache {
	
	private $prefix;
	
	public function __construct($Configuration){
		$this->prefix = rtrim($Configuration['prefix'], '/').'/';
	}
	
	public function retrieve($id){
		return apc_fetch($this->prefix.$id);
	}
	
	public function store($id, $content, $ttl = null){
		apc_store($this->prefix.$id, $content, $ttl);
	}
	
	public function erase($array){
		foreach($array as $id)
			apc_delete($this->prefix.$id);
	}
	
	public function isAvailable(){
		return function_exists('apc_fetch');
	}
	
}