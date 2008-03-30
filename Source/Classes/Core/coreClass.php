<?php
class Core {
	private static $Initialized = false,
		$Configuration = array(),
		$onInitialize = array();
	
	public static function classFileExists($class){
		$class = strtolower($class);
		
		$Classes = self::retrieve('Classes');
		if(class_exists($class) || $Classes[$class])
			return $Classes[$class];
		
		return false;
	}
	
	public static function autoloadClass($class){
		$file = self::classFileExists($class);
		
		if($file && !class_exists($class))
			require_once($file);
		
		return true;
	}
	
	/**
	 * This method loads the given class and only returns false, when the given
	 * classfile does not exist.
	 *
	 * @param string $dir
	 * @param string $class
	 * @return bool
	 */
	public static function loadClass($dir, $class){
		$file = self::retrieve('path').'Classes/'.$dir.'/'.strtolower($class).'Class.php';
		if(!file_exists($file))
			return false;
		
		if(!class_exists($class))
			require_once($file);
		
		return true;
	}
	
	public static function initialize(){
		if(self::$Initialized) return;
		
		/* @var $c Cache */
		$c = Cache::getInstance(self::retrieve('cacheOptions', array()));
		
		$Classes = $c->retrieve('Core', 'Classes');
		if(!$Classes){
			$files = glob(self::retrieve('path').'/Classes/*/*.php');
			if(is_array($files))
				foreach($files as $file)
					$Classes[substr(basename($file, '.php'), 0, -5)] = $file;
			
			if($Classes) $c->store('Core', 'Classes', $Classes);
		}
		self::store('Classes', $Classes);
		
		self::$Initialized = true;
		foreach(self::$onInitialize as $value)
			foreach($value as $key =>$val)
				call_user_func_array(array('Core', $key), $val);
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
		
		$Classes = self::retrieve('Classes');
		foreach($classes as $val)
			$Classes[$val] = $Classes[$base];
		
		self::store('Classes', $Classes);
	}
}
?>