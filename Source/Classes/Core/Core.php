<?php
class Core extends StaticStorage {
	private static $Initialized = false,
		$onInitialize = array();
	
	private function __construct(){}
	private function __clone(){}
	
	public static function classFileExists($class, $toLoad = null){
		$toLoad = in_array($toLoad, array('Classes', 'Layers')) ? $toLoad : 'Classes';
		
		$class = ucfirst(strtolower($class));
		if($toLoad=='Layers')
			$class .= 'Layer';
		
		$List = self::retrieve($toLoad);
		
		return class_exists($class) || $List[$class] ? $List[$class] : false;	
	}
	
	public static function autoload($class, $toLoad = null){
		$file = self::classFileExists($class, $toLoad);
		
		if($file && !class_exists($class))
			require_once($file);
		
		return $file;
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
		$file = self::retrieve('path').'Classes/'.$dir.'/'.strtolower($class).'.php';
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
		
		$isDebug = self::retrieve('debugMode');
		
		foreach(array(
			'Classes' => glob(self::retrieve('path').'/Classes/*/*.php'),
			'Layers' => glob(self::retrieve('appPath').'/Layers/*.php'),
		) as $key => $files){
			$List = $c->retrieve('Core', $key);
			if(!$List || $isDebug){
				if(is_array($files))
					foreach($files as $file)
						$List[basename($file, '.php')] = $file;
				
				if($List) $c->store('Core', $key, $List);
			}
			self::store($key, $List);
		}
		
		self::$Initialized = true;
		foreach(self::$onInitialize as $value)
			foreach($value as $key =>$val)
				call_user_func_array(array('Core', $key), $val);
		
		self::pollute();
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
	
	public static function pollute(){
		$polluted = array();
		$vars = explode('/', $_SERVER['PATH_INFO']);
		
		foreach($vars as $v){
			$v = Util::cleanWhitespaces($v);
			if(!$v) continue;
			
			$v = explode(':', $v, 2);
			if($polluted['p'][$v[0]]) continue;
			
			$polluted['p'][$v[0]] = $v[1] ? $v[1] : $v[0];
			$polluted['n'][] = $v[0];
		}
		
		foreach(array('index', 'default') as $k => $v)
			if(!$polluted['n'][$k])
				$polluted['p'][$v] = $polluted['n'][$k] = $v;
		
		$_GET = array_merge($_GET, $polluted);
	}
}
?>