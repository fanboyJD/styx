<?php
/*
 * Styx::Filecache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface for Harddisk-Storage
 *
 */


class Filecache {
	
	private $Configuration = array(
			'prefix' => null,
		);
	
	public function __construct($Configuration){
		$this->Configuration['prefix'] = $Configuration['root'].$Configuration['prefix'].'/';
	}
	
	public function retrieve($id){
		$file = $this->Configuration['prefix'].$id.'.txt';
		if(!file_exists($file)) return null;
		
		return file_get_contents($file);
	}
	
	public function store($id, $content, $ttl = null){
		$file = $this->Configuration['prefix'].$id.'.txt';
		
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
	
	public function erase($array){
		foreach($array as $id){
			$file = $this->Configuration['prefix'].$id.'.txt';
			
			if(file_exists($file)) unlink($file);
		}
	}
	
	public function isAvailable(){
		return true;
	}
	
}