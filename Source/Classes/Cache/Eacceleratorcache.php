<?php
class Eacceleratorcache {
	
	public $prefix = null,
		$root = null;
	
	public function __construct($prefix, $root){
		$this->prefix = $prefix;
		$this->root = $root;
	}
	
	public function retrieve($key){
		return eaccelerator_get($this->prefix.$key);
	}
	
	public function store($key, $content, $ttl){
		eaccelerator_put($this->prefix.$key, $content, $ttl);
	}
	
	public function erase($key, $force = false){
		eaccelerator_lock($this->prefix.$key);
		eaccelerator_rm($this->prefix.$key);
		eaccelerator_unlock($this->prefix.$key);
	}
	
	public function eraseBy($key){
		$prefix = explode('/', $key);
		$keys = eaccelerator_list_keys();
		foreach($keys as $val)
			if(startsWith($val['name'], ':'.$this->prefix.$key))
				$this->erase($prefix[0].'/'.substr($val['name'], strrpos($val['name'], '/')+1));
	}
	
	public function eraseAll(){
		$keys = eaccelerator_list_keys();
		foreach($keys as $val)
			if(startsWith($val['name'], ':'.$this->prefix))
				$this->erase(substr($val['name'], strrpos($val['name'], $this->prefix)+strlen($this->prefix)));
	}
}