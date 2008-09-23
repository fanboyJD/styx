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
	
	public static function pollute(){
		$polluted = array();
		
		$vars = explode('/', $_SERVER['PATH_INFO']);
		array_shift($vars);
		
		$version = self::retrieve('app.version');
		$separator = self::retrieve('path.separator');
		
		foreach($vars as $k => $v){
			$v = Data::clean($v);
			if(!$v) continue;
			
			$v = explode($separator, $v, 2);
			if($polluted['p'][$v[0]]) continue;
			
			if(!$k && $version==$v[0] && strpos($vars[$k+1], '.')){
				$polluted['m']['package'] = $vars[$k+1];
				continue;
			}elseif($v[0]=='lang'){
				$polluted['m']['lang'] = $v[1];
				continue;
			}
			
			$polluted['p'][$v[0]] = pick($v[1], null);
			if($v[0]!='handler') $polluted['n'][] = $v[0];
		}
		
		foreach(array('index', 'view') as $k => $v)
			if(!$polluted['n'][$k]){
				$polluted['n'][$k] = $v;
				$polluted['p'][$v] = null;
			}
		
		if(!$polluted['p']['handler']) $polluted['p']['handler'] = 'html';
		
		unset($_GET['n'], $_GET['p'], $_GET['m']);
		$_GET = array_merge($_GET, $polluted);
		
		if(sizeof($_POST) && get_magic_quotes_gpc())
			foreach($_POST as &$val)
				$val = stripslashes($val);
	}
	
	public static function mkdir($path, $mode = 0777){
		return is_dir($path) || (self::mkdir(dirname($path), $mode) && self::rmkdir($path, $mode));
	}
	
	private static function rmkdir($path, $mode = 0777){
		try{
			$old = umask(0);
			$res = mkdir($path, $mode);
			umask($old);
		}catch(Exception $e){}
		
		return $res;
	}
	
}