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
		if(!$this->it->isDir())
			return strtolower(pathinfo($this->current(), PATHINFO_EXTENSION))=='php';
		
		return true;
	}
	
}

abstract class Runner {
	
	public function execute(){
		include(func_get_arg(0));
	}
	
}

class Core extends StaticStorage {
	
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
		$file = self::retrieve('path').'Classes/'.$dir.'/'.ucfirst(strtolower($class)).'.php';
		
		if(!file_exists($file)) return false;
		
		if(!class_exists($class)) require_once($file);
		
		return true;
	}
	
	public static function classExists($class){
		$class = strtolower($class);
		
		$List = self::retrieve('Classes');
		
		return $List[$class] || class_exists($class) ? $List[$class] : false;	
	}
	
	public static function autoload($class){
		$file = self::classExists($class);
		
		if($file && !class_exists($class)) require_once($file);
		
		return $file;
	}
	
	public static function initialize(){
		$c = Cache::getInstance();
		
		$List = self::store('Classes', $c->retrieve('Core', 'Classes'));
		
		if(!$List || self::retrieve('debug')){
			$List = array();
			
			$apppath = self::retrieve('app.path');
			foreach(array(
				glob(self::retrieve('path').'/Classes/*/*.php'),
				glob($apppath.'/Layers/*.php'),
			) as $files)
				if(is_array($files) && sizeof($files))
					foreach($files as $file)
						$List[strtolower(basename($file, '.php'))] = $file;
			
			foreach(new PHPExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apppath.'/Assets/'))) as $file)
				$List[strtolower(basename($file, '.php'))] = $file;
			
			self::store('Classes', $c->store('Core', 'Classes', $List));
		}
	}
	
}