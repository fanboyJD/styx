<?php
class Env {
	private static $Initialized = false,
		$Configuration = array(),
		$onInitialize = array();
	
	public static function initialize($basePath = null){
		if(self::$Initialized) return;
		
		if($basePath) self::store('basePath', $basePath);
		
		/* @var $c Cache */
		$c = Cache::getInstance(self::retrieve('cacheOptions', array()));
		
		$Classes = $c->retrieve('Env', 'Classes');
		if(!$Classes){
			$files = glob('./Classes/*/*.php');
			if(is_array($files))
				foreach($files as $file)
					$Classes[basename($file, '.php')] = $file;
			
			if($Classes) $c->store('Env', 'Classes', $Classes);
		}
		self::store('Classes', $Classes);
		self::$Initialized = true;
		foreach(self::$onInitialize as $value)
			foreach($value as $key =>$val)
				call_user_func_array(array('Env', $key), $val);
	}
	
	public static function store($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				self::store($k, $val);
			
			return;
		}
		if(!self::$Configuration[$key] || self::$Configuration[$key]!=$value){
			self::$Configuration[$key] = $value;
			if(!$value) unset(self::$Configuration[$key]);
		}
	}
	
	public static function retrieve($key, $value = null){
		if(!self::$Configuration[$key])
			self::store($key, $value);
		
		return self::$Configuration[$key];
	}
	
	public static function erase($key){
		unset(self::$Configuration[$key]);
	}
	
	public static function registerClasses($base, $classes){
		if(!self::$Initialized){
			self::$onInitialize[] = array(
				'registerClasses' => array($base, $classes),
			);
			return;
		}
		
		$Classes = Env::retrieve('Classes');
		foreach($classes as $val)
			$Classes[$val.'Class'] = $Classes[$base.'Class'];
		
		Env::store('Classes', $Classes);
	}
}
?>