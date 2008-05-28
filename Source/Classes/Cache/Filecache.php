<?php
class Filecache {
	
	public $prefix = null,
		$root = null,
		$time = null;
	
	public function __construct($prefix, $root){
		$this->prefix = $prefix;
		$this->root = $root;
		
		$this->time = time();
	}
	
	public function retrieve($key){
		$file = $this->root.$this->prefix.'/'.$key.'.txt';
		if(!file_exists($file)) return null;
		
		$content = explode('|', file_get_contents($file), 2);
		
		return $content[0]<$this->time && $content[0]!='file' ? null : $content[1];
	}
	
	public function store($key, $content, $ttl){
		$file = $this->root.$this->prefix.'/'.$key.'.txt';
		if(!file_exists($file)){
			try{
				$dir = dirname($file);
				if(!is_dir($dir)){
					if(!is_dir($this->root.$this->prefix))
						mkdir($this->root.$this->prefix, 0777);
					
					mkdir($dir, 0777);
				}
				touch($file); 
				chmod($file, 0777);
			}catch(Exception $e){}
		}
		file_put_contents($file, ($ttl=='file' ? 'file' : $this->time+$ttl).'|'.$content);
	}
	
	public function erase($key, $force = false){
		$files = glob($this->root.$this->prefix.'/'.$key.'.txt');
		if(!is_array($files))
			return;
		
		try{
			foreach($files as $file){
				$content = explode('|', file_get_contents($file), 2);
				if($content[0]=='file' && $force!='file')
					continue;
				unlink($file);
			}
		}catch(Exception $e){}
	}
}