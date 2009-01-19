<?php
/**
 * Styx::Filecache - Cache-Interface for Harddisk-Storage
 *
 * @package Styx
 * @subpackage Cache
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Filecache {
	
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
		$this->prefix = $Configuration['root'].'/'.$Configuration['prefix'].'/';
	}
	
	/**
	 * Fetches a value from the cache
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function retrieve($id){
		$file = $this->prefix.$id.'.txt';
		
		if(!file_exists($file)) return null;
		
		return file_get_contents($file);
	}
	
	/**
	 * Stores a value in the cache
	 *
	 * @param string $id
	 * @param mixed $content
	 * @param int $ttl
	 */
	public function store($id, $content, $ttl = null){
		$file = $this->prefix.$id.'.txt';
		
		if(!file_exists($file)){
			static $loaded;
			if(!$loaded){
				Core::loadClass('Utility', 'Folder');
				$loaded = true;
			}
			
			Folder::mkdir(dirname($file));
			
			touch($file); 
			chmod($file, 0777);
		}
		
		file_put_contents($file, $content);
	}
	
	/**
	 * Removes the given elements from the cache
	 *
	 * @param array $array
	 */
	public function erase($array){
		foreach($array as $id){
			$file = $this->prefix.$id.'.txt';
			
			if(file_exists($file)) unlink($file);
		}
	}
	
	/**
	 * Checks whether the extension is available or not
	 *
	 * @return bool
	 */
	public static function isAvailable(){
		return true;
	}
	
}