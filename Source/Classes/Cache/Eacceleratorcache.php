<?php
/**
 * Styx::Eacceleratorcache - Cache-Interface for the eAccelerator-extension
 *
 * @package Styx
 * @subpackage Cache
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Eacceleratorcache {
	
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
		return eaccelerator_get($this->prefix.$id);
	}
	
	/**
	 * Stores a value in the cache
	 *
	 * @param string $id
	 * @param mixed $content
	 * @param int $ttl
	 */
	public function store($id, $content, $ttl = null){
		eaccelerator_put($this->prefix.$id, $content, $ttl);
	}
	
	/**
	 * Removes the given elements from the cache
	 *
	 * @param array $array
	 */
	public function erase($array){
		foreach($array as $id)
			eaccelerator_rm($this->prefix.$id);
	}
	
	/**
	 * Checks whether the extension is available or not
	 *
	 * @return bool
	 */
	public static function isAvailable(){
		return function_exists('eaccelerator_get');
	}
	
}