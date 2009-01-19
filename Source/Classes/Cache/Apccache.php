<?php
/**
 * Styx::Apccache - Cache-Interface for APC
 *
 * @package Styx
 * @subpackage Cache
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Apccache {
	
	/**
	 * The prefix to be used to not interfere with other Applications on the same server
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Sets up the backend engine
	 *
	 * @param array $Configuration
	 */
	public function __construct($Configuration){
		$this->prefix = rtrim($Configuration['prefix'], '/').'/';
	}
	
	/**
	 * Fetches a value from the cache
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function retrieve($id){
		return apc_fetch($this->prefix.$id);
	}
	
	/**
	 * Stores a value in the cache
	 *
	 * @param string $id
	 * @param mixed $content
	 * @param int $ttl
	 */
	public function store($id, $content, $ttl = null){
		apc_store($this->prefix.$id, $content, $ttl);
	}
	
	/**
	 * Removes the given elements from the cache
	 *
	 * @param array $array
	 */
	public function erase($array){
		foreach($array as $id)
			apc_delete($this->prefix.$id);
	}
	
	/**
	 * Checks whether the extension is available or not
	 *
	 * @return bool
	 */
	public static function isAvailable(){
		return function_exists('apc_fetch');
	}
	
}