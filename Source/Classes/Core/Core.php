<?php
/*
 * Styx::Core - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Initializes the Styx Framework and handles basic stuff
 *
 */

function pick($a, $b = null){
	return $a ? $a : $b;
}

class ExtensionFilter extends FilterIterator {
	
	private $it,
		$ext = array();
	
	public function __construct(RecursiveIteratorIterator $it, $ext = null){
		if(!$ext) $ext = 'php';
		
		$this->ext = Hash::splat($ext);
		
		parent::__construct($it);
		$this->it = $it;
	}
	
	public function accept(){
		return !empty($this->it->isDir) || in_array(strtolower(pathinfo($this->current(), PATHINFO_EXTENSION)), $this->ext);
	}
	
}

abstract class Runner {
	
	public function execute(){
		include(func_get_arg(0));
	}
	
}

class Core {
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * This method is only needed to load the basic Framework
	 * Classes before it is initialized. As it is only used internally
	 * no additional checks (e.g. file_exists) are done.
	 *
	 * @param string $dir
	 * @param string $class
	 * @return bool
	 */
	public static function loadClass($dir, $class){
		$file = self::$Storage['path'].'Classes/'.ucfirst($dir).'/'.ucfirst(strtolower($class)).'.php';
		
		if(!class_exists($class, false)) require $file;
		
		return true;
	}
	
	public static function classExists($class){
		$class = strtolower($class);
		
		return !empty(self::$Storage['Classes'][$class]) || class_exists($class, false) ? self::$Storage['Classes'][$class] : false;	
	}
	
	public static function autoload($class){
		$file = self::classExists($class);
		
		if($file && !class_exists($class, false)) require $file;
		
		return !!$file;
	}
	
	public static function initialize(){
		self::$Storage['identifier'] = array(
			'internal' => self::$Storage['identifier.internal'],
			'external' => self::$Storage['identifier.external'],
		);
		
		self::loadClass('Cache', 'Cache');
		
		$c = Cache::getInstance();
		
		$List = self::$Storage['Classes'] = $c->retrieve('Core/Classes');
		
		if(!$List || !empty(self::$Storage['debug'])){
			$List = array();
			
			foreach(array(
				glob(self::$Storage['path'].'/Classes/*/*.php'),
				glob(self::$Storage['app.path'].'/Layers/*.php'),
			) as $files)
				if(Hash::length($files))
					foreach($files as $file)
						$List[strtolower(basename($file, '.php'))] = $file;
			
			foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$Storage['app.path'].'/Classes/'))) as $file)
				$List[strtolower(basename($file->getFileName(), '.php'))] = $file->getRealPath();
			
			self::$Storage['Classes'] = $c->store('Core/Classes', $List, ONE_WEEK);
		}
		
		$Templates = self::$Storage['Templates'] = $c->retrieve('Core/Templates');
		
		if(!$Templates || !empty(self::$Storage['debug'])){
			$Templates = array();
			
			foreach(array(
				realpath(self::$Storage['path'].'/Templates/'),
				realpath(self::$Storage['app.path'].'/Templates/'),
			) as $path){
				$length = strlen($path)+1;
				
				foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), array('tpl', 'php')) as $file){
					$realpath = $file->getRealPath();
					
					$Templates[str_replace('\\', '/', substr($realpath, $length))] = $realpath;
				}
			}
			
			self::$Storage['Templates'] = $c->store('Core/Templates', $Templates, ONE_WEEK);
		}
	}
	
	public static function fireEvent($event){
		static $Instance, $Methods = array();
		
		if($Instance===null)
			$Instance = self::classExists('Application') ? new Application : false;
		
		if($Instance===false)
			return false;
		
		if(!Hash::length($Methods))
			foreach(get_class_methods($Instance) as $method)
				if(String::starts($method, 'on') && strlen($method)>=3)
					array_push($Methods, strtolower(substr($method, 2)));
		
		$event = strtolower($event);
		
		if(!in_array($event, $Methods))
			return false;
		
		$Instance->{'on'.ucfirst($event)}();
		
		return true;
	}
	
	/* Storage Methods (Will be moved to a StaticStorage-Class in PHP5.3) */
	private static $Storage = array();
	
	public static function store($array, $value = null){
		if(!is_array($array))
			$array = array($array => $value);
		
		foreach($array as $key => $value)
			if(empty(self::$Storage[$key]) || self::$Storage[$key]!=$value){
				if($value) self::$Storage[$key] = $value;
				else unset(self::$Storage[$key]);
			}
		
		return Hash::length($array)==1 ? $value : $array;
	}
	
	public static function retrieve($key, $value = null){
		if($value && empty(self::$Storage[$key]))
			return self::store($key, $value);
		
		return !empty(self::$Storage[$key]) ? self::$Storage[$key] : null;
	}
	
	public static function fetch(){
		$args = Hash::args(func_get_args());
		
		$array = array();
		
		foreach($args as $arg)
			if(!empty(self::$Storage[$arg]))
				$array[$arg] = self::$Storage[$arg];
		
		return $array;
	}
	
}