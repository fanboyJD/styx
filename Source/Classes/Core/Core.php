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

class PHPExtensionFilter extends FilterIterator {
	
	private $it;
	
	public function __construct(RecursiveIteratorIterator $it){
		parent::__construct($it);
		
		$this->it = $it;
	}
	
	public function accept(){
		return !empty($this->it->isDir) || strtolower(pathinfo($this->current(), PATHINFO_EXTENSION))=='php';
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
	 * This method loads the given class and only returns false, if the given
	 * classfile does not exist. This method is only needed to load the basic
	 * Framework Classes before it is initialized.
	 *
	 * @param string $dir
	 * @param string $class
	 * @return bool
	 */
	public static function loadClass($dir, $class){
		$file = self::retrieve('path').'Classes/'.ucfirst($dir).'/'.ucfirst(strtolower($class)).'.php';
		
		if(!file_exists($file)) return false;
		
		if(!class_exists($class, false)) require $file;
		
		return true;
	}
	
	public static function classExists($class){
		$class = strtolower($class);
		
		$List = self::retrieve('Classes');
		
		return !empty($List[$class]) || class_exists($class, false) ? $List[$class] : false;	
	}
	
	public static function autoload($class){
		$file = self::classExists($class);
		
		if($file && !class_exists($class, false)) require $file;
		
		return !!$file;
	}
	
	public static function initialize(){
		self::loadClass('Cache', 'Cache');
		
		$c = Cache::getInstance();
		
		$List = self::store('Classes', $c->retrieve('Core', 'Classes'));
		
		if(!$List || self::retrieve('debug')){
			$List = array();
			
			$apppath = self::retrieve('app.path');
			foreach(array(
				glob(self::retrieve('path').'/Classes/*/*.php'),
				glob($apppath.'/Layers/*.php'),
			) as $files)
				if(Hash::length($files))
					foreach($files as $file)
						$List[strtolower(basename($file, '.php'))] = $file;
			
			foreach(new PHPExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apppath.'/Classes/'))) as $file)
				$List[strtolower(basename($file->getFileName(), '.php'))] = $file->getRealPath();
			
			$c->store('Core', 'Classes', self::store('Classes', $List));
		}
	}
	
	/* Using this because of missing features (will be changed when a usable version of php5.3 is out) */
	private static function map($fn, $args){
		static $Storage;
		
		return call_user_func_array(array($Storage ? $Storage : $Storage = new Storage(), $fn), $args);
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