<?php
class DynamicStorage {
	
	protected $Storage = array();
	
	public function store($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				$this->store($k, $val);
			
			return $key;
		}
		
		if(!$this->Storage[$key] || $this->Storage[$key]!=$value){
			$this->Storage[$key] = $value;
			if(!$value) unset($this->Storage[$key]);
		}
		
		return $value;
	}
	
	public function retrieve($key, $value = null){
		if($value && !$this->Storage[$key])
			$this->store($key, $value);
		
		return $this->Storage[$key];
	}
	
	public function erase($key){
		unset($this->Storage[$key]);
	}
	
	public function eraseBy($key){
		foreach($this->Storage as $k => $v)
			if(startsWith($k, $key))
				unset($this->Storage[$k]);
	}
	
	public function eraseAll(){
		$this->Storage = array();
	}
	
}

class StaticStorage {
	
	private static $StorageInstance;
	
	private static function map($fn, $args){
		if(!self::$StorageInstance)
			self::$StorageInstance = new DynamicStorage();
		
		return call_user_func_array(array(self::$StorageInstance, $fn), $args);
	}
	
	public static function store(){
		$args = func_get_args();
		return self::map('store', $args);
	}
	
	public static function retrieve(){
		$args = func_get_args();
		return self::map('retrieve', $args);
	}
	
	public static function erase(){
		$args = func_get_args();
		return self::map('erase', $args);
	}
	
	public static function eraseBy(){
		$args = func_get_args();
		return self::map('eraseBy', $args);
	}
	
	public static function eraseAll(){
		$args = func_get_args();
		return self::map('eraseAll', $args);
	}
	
}