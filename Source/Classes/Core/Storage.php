<?php
class Storage {
	private static $Storage = array();
	
	public static function store($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				self::store($k, $val);
			
			return;
		}
		if(!self::$Storage[$key] || self::$Storage[$key]!=$value){
			self::$Storage[$key] = $value;
			if(!$value) unset(self::$Storage[$key]);
		}
	}
	
	public static function retrieve($key, $value = null){
		if(!self::$Storage[$key])
			self::store($key, $value);
		
		return self::$Storage[$key];
	}
	
	public static function erase($key){
		unset(self::$Storage[$key]);
	}
	
	public static function eraseBy($key){
		foreach(self::$Storage as $k => $v)
			if(Util::startsWith($k, $key))
				unset(self::$Storage[$k]);
	}
	
	public static function eraseAll(){
		self::$Storage = array();
	}
}

class DynamicStorage {
	private $Storage = array();
	
	public function store($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				$this->store($k, $val);
			
			return;
		}
		if(!$this->Storage[$key] || $this->Storage[$key]!=$value){
			$this->Storage[$key] = $value;
			if(!$value) unset($this->Storage[$key]);
		}
	}
	
	public function retrieve($key, $value = null){
		if(!$this->Storage[$key])
			self::store($key, $value);
		
		return $this->Storage[$key];
	}
	
	public function erase($key){
		unset($this->Storage[$key]);
	}
	
	public function eraseBy($key){
		foreach($this->Storage as $k => $v)
			if(Util::startsWith($k, $key))
				unset($this->Storage[$k]);
	}
	
	public function eraseAll(){
		$this->Storage = array();
	}
}
?>