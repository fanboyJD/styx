<?php
/**
 * Returns either {@link $a} if not empty, or {@link $b}
 *
 * @package Styx
 * @subpackage Core
 *
 * @param mixed $a
 * @param mixed $b
 * @return mixed
 */
function pick($a, $b = null){
	return $a ? $a : $b;
}

/**
 * Styx::ExtensionFilter - Returns only the files by the specified extension
 * when used with a DirectoryIterator
 *
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 *
 */
class ExtensionFilter extends FilterIterator {
	
	private $it,
		$ext = array();
	
	/**
	 * @param RecursiveIteratorIterator $it
	 * @param string|array $ext
	 */
	public function __construct(RecursiveIteratorIterator $it, $ext = null){
		if(!$ext) $ext = 'php';
		
		$this->ext = Hash::splat($ext);
		
		parent::__construct($it);
		$this->it = $it;
	}
	
	/**
	 * Checks if the file matches one of the specified extensions
	 *
	 * @return bool Whether to accept the file or not
	 */
	public function accept(){
		return !$this->it->isLink() && ($this->it->isDir() || in_array(String::toLower(pathinfo($this->current(), PATHINFO_EXTENSION)), $this->ext));
	}
	
}

/**
 * A class can extend from Runner to be used with {@link Template::bind}
 *
 * @see Template::bind
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */
abstract class Runner {
	
	/**
	 * A template that is bound to a class uses this method to execute in class-scope
	 *
	 * @param array $assigned All variables that are assigned to the array get passed
	 */
	public function execute($assigned){
		include(func_get_arg(1));
	}
	
}

/**
 * Styx::Core - Initializes the Styx Framework, holds the Configuration and 
 * provides some basic functionality
 *
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */
class Core {
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * This method is only needed to load the basic Framework-Classes
	 * before it is initialized. As it is only used internally
	 * no additional checks (e.g. file_exists) are made
	 *
	 * @param string $dir
	 * @param string $class
	 * @return bool Always true
	 */
	public static function loadClass($dir, $class){
		$file = self::$Storage['path'].'Classes/'.String::ucfirst($dir).'/'.String::ucfirst($class).'.php';
		
		if(!class_exists($class, false)) require $file;
		
		return true;
	}
	
	/**
	 * Returns the filename of a class if it exists anywhere in the Framework
	 * or in the Application. The class might not be loaded at that point as
	 * it only checks if the given class-file exists
	 *
	 * @param string $class
	 * @return mixed 
	 */
	public static function classExists($class){
		$class = String::toLower($class);
		
		return !empty(self::$Storage['Classes'][$class]) || class_exists($class, false) ? self::$Storage['Classes'][$class] : false;	
	}
	
	/**
	 * Automatically loads the given class when used with "new Classname" or a static call to one of its methods (Classname::method())
	 *
	 * @param string $class
	 * @return bool
	 */
	public static function autoload($class){
		$file = self::classExists($class);
		
		if($file && !class_exists($class, false)) require $file;
		
		return !!$file;
	}
	
	/**
	 * This method sets up the basic configuration of the Framework
	 * and initializes the classes and templates that are available
	 * in the Framework or the Application
	 *
	 */
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
						$List[String::toLower(basename($file, '.php'))] = $file;
			
			foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$Storage['app.path'].'/Classes/'))) as $file)
				$List[String::toLower(basename($file->getFileName(), '.php'))] = $file->getRealPath();
			
			foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$Storage['app.path'].'/Prototypes/'))) as $file)
				$List[String::toLower(basename($file->getFileName(), '.php'))] = $file->getRealPath();
			
			self::$Storage['Classes'] = $c->store('Core/Classes', $List, ONE_WEEK);
		}
		
		$Templates = self::$Storage['Templates'] = $c->retrieve('Core/Templates');
		
		if(!$Templates || !empty(self::$Storage['debug'])){
			$Templates = array();
			
			foreach(array(
				realpath(self::$Storage['path'].'/Templates/'),
				realpath(self::$Storage['app.path'].'/Templates/'),
			) as $path){
				$length = String::length($path)+1;
				
				foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), array('tpl', 'php')) as $file){
					$realpath = $file->getRealPath();
					
					$Templates[String::replace('\\', '/', String::sub($realpath, $length))] = $realpath;
				}
			}
			
			self::$Storage['Templates'] = $c->store('Core/Templates', $Templates, ONE_WEEK);
		}
	}
	
	/**
	 * Tries to call the static method given by {@link $event} on the Application Class
	 * if the class and the method are available. This can be used at any time for any
	 * custom event
	 *
	 * <b>Predefined Events</b>
	 * <ul>
	 * <li>initialize - Gets called before the magic happens, used to set up routes, handle a logged in user etc.</li>
	 * <li>pageShow - Gets called shortly before the Page outputs the html and after most of the processing is done</li>
	 * </ul>
	 *
	 * @param string $event
	 * @return mixed Returns the event return-value or false if there is no Application class or the event does not exist
	 */
	public static function fireEvent($event){
		static $Instance, $Methods = array();
		
		if($Instance===null)
			$Instance = self::classExists('Application') ? new Application : false;
		
		if($Instance===false)
			return false;
		
		if(!Hash::length($Methods))
			foreach(get_class_methods($Instance) as $method)
				if(String::starts($method, 'on') && String::length($method)>=3)
					array_push($Methods, String::toLower(String::sub($method, 2)));
		
		$event = String::toLower($event);
		
		if(!in_array($event, $Methods))
			return false;
		
		return $Instance->{'on'.String::ucfirst($event)}();
	}
	
	/* Storage Methods (Will be moved to a StaticStorage-Class in PHP5.3) */
	/**
	 * Holds all the stored variables
	 *
	 * @var array
	 */
	private static $Storage = array();
	
	/**
	 * Stores a single value or an array of key/value pairs
	 *
	 * @param array|string $array
	 * @param mixed $value
	 * @return mixed
	 */
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
	
	/**
	 * Returns the value for a given key. If the second parameter is set,
	 * it stores it and returns it only if the given key has not been set yet
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public static function retrieve($key, $value = null){
		if($value && empty(self::$Storage[$key]))
			return self::store($key, $value);
		
		return !empty(self::$Storage[$key]) ? self::$Storage[$key] : null;
	}
	
	/**
	 * Retrieves the values to all given keys (given by one array or many arguments)
	 *
	 * @return array
	 */
	public static function fetch(){
		$args = Hash::args(func_get_args());
		
		$array = array();
		
		foreach($args as $arg)
			if(!empty(self::$Storage[$arg]))
				$array[$arg] = self::$Storage[$arg];
		
		return $array;
	}
	
}