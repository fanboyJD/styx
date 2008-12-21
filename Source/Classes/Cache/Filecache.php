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
		),
		$time = null;
	
	public function __construct($Configuration){
		$this->Configuration['prefix'] = $Configuration['root'].$Configuration['prefix'].'/';
		
		$this->time = time();
	}
	
	public function retrieve($key){
		$file = $this->Configuration['prefix'].$key.'.txt';
		if(!file_exists($file)) return null;
		
		$content = explode('|', file_get_contents($file), 2);
		
		return $content[0]<$this->time && $content[0]!='file' ? null : $content[1];
	}
	
	public function store($key, $content, $ttl){
		$file = $this->Configuration['prefix'].$key.'.txt';
		
		if(!file_exists($file)){
			Folder::mkdir(dirname($file));
			
			try{
				touch($file); 
				chmod($file, 0777);
			}catch(Exception $e){}
		}
		
		file_put_contents($file, ($ttl=='file' ? 'file' : $this->time+$ttl).'|'.$content);
	}
	
	public function erase($key, $force = false){
		$files = glob($this->Configuration['prefix'].$key.'.txt');
		if(!Hash::length($files))
			return;
		
		try{
			foreach($files as $file){
				$content = explode('|', file_get_contents($file), 2);
				if($content[0]=='file' && !$force)
					continue;
				
				unlink($file);
			}
		}catch(Exception $e){}
	}
	
	public function eraseBy($key, $force = false){
		$this->erase($key.'*', $force);
	}
	
	public function eraseAll($force = false){
		$this->erase('*/*', $force);
	}
}