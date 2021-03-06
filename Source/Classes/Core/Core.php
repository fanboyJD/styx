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
		return !$this->it->isLink() && ($this->it->isDir() || in_array(strtolower(pathinfo($this->current(), PATHINFO_EXTENSION)), $this->ext));
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
	
	/**
	 * A list of classes that are in the file with the classname specified as key in this array
	 *
	 * @var array
	 */
	private static $Classes = array(
		'element' => array(
			'elements', 'formelement', 'inputelement', 'hiddenelement', 'uploadelement', 'buttonelement',
			'radioelement', 'selectelement', 'checkboxelement', 'textareaelement', 'richtextelement'
		),
	);
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * This method is only needed to load the basic Framework-Classes
	 * before it is initialized. As it is only used internally
	 * no additional checks (e.g. file_exists) are made
	 *
	 * @param string $dir
	 * @param string $class
	 */
	public static function loadClass($dir, $class){
		$file = self::$Storage['path'].'Classes/'.ucfirst($dir).'/'.ucfirst($class).'.php';
		
		if(!class_exists($class, false)) require $file;
	}
	
	/**
	 * Returns true if a class exists anywhere in the Framework
	 * or in the Application. The class might not be loaded at that point as
	 * it only checks if the given class-file exists
	 *
	 * @param string $class
	 * @return bool 
	 */
	public static function classExists($class){
		return !empty(self::$Storage['Classes'][$class = strtolower($class)]) || class_exists($class, false);	
	}
	
	/**
	 * Automatically loads the given class when used with "new Classname" or a static call to one of its methods (Classname::method())
	 *
	 * @param string $class
	 * @return bool
	 */
	public static function autoload($class){
		$class = strtolower($class);
		
		if(empty(self::$Storage['Classes'][$class]))
			return false;
		
		if(!class_exists($class, false)) require self::$Storage['Classes'][$class];
		
		return true;
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
		
		self::$Storage['Classes'] = $c->retrieve('Core/Classes');
		if(!self::$Storage['Classes'] || !empty(self::$Storage['debug'])){
			self::$Storage['Classes'] = self::getClassList('Classes', 'path');
			
			foreach(self::$Classes as $class => $classes)
				foreach($classes as $mapping)
					self::$Storage['Classes'][$mapping] = self::$Storage['Classes'][$class];
			
			self::$Storage['Classes'] = array_merge(self::$Storage['Classes'],
				self::getClassList('Classes'),
				self::getClassList('Layers'),
				self::getClassList('Models'),
				self::getClassList('Modules'),
				self::getClassList('Objects')
			);
			
			$c->store('Core/Classes', self::$Storage['Classes'], ONE_WEEK);
		}
		
		require_once(self::$Storage['path'].'PrototypeInstantiation.php');
		
		self::$Storage['Methods'] = $c->retrieve('Core/Methods');
		if(!self::$Storage['Methods'] || !empty(self::$Storage['debug'])){
			self::$Storage['Methods'] = array();
			foreach(self::$Storage['Classes'] as $class => $v)
				if($class=='application' || String::ends($class, 'layer')){
					self::$Storage['Methods'][$class] = array();
					foreach(get_class_methods($class) as $method)
						if(String::starts($method, 'on') && strlen($method)>=3)
							array_push(self::$Storage['Methods'][$class], strtolower(substr($method, 2)));
				}
			
			foreach(array('data', 'validator') as $class){
				self::$Storage['Methods'][$class] = array();
				foreach(get_class_methods($class) as $method)
					if(!String::starts($method, '__') && $method!='call')
						array_push(self::$Storage['Methods'][$class], strtolower($method));
			}
			
			$c->store('Core/Methods', self::$Storage['Methods'], ONE_WEEK);
		}
		
		self::$Storage['Templates'] = $c->retrieve('Core/Templates');
		if(!self::$Storage['Templates'] || !empty(self::$Storage['debug'])){
			self::$Storage['Templates'] = array();
			foreach(array(
				realpath(self::$Storage['path'].'/Templates/'),
				realpath(self::$Storage['app.path'].'/Templates/'),
			) as $path){
				$length = strlen($path)+1;
				
				foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), array('tpl', 'php')) as $file){
					$realpath = $file->getRealPath();
					
					self::$Storage['Templates'][str_replace('\\', '/', substr($realpath, $length))] = $realpath;
				}
			}
			
			$c->store('Core/Templates', self::$Storage['Templates'], ONE_WEEK);
		}
	}
	
	/**
	 * Returns a list with all PHP-Files in the given Folder inside the application
	 *
	 * @param string $folder
	 * @return array
	 */
	protected static function getClassList($folder, $path = 'app.path'){
		$files = array();
		
		if(is_dir($folder = self::$Storage[$path].'/'.$folder))
			foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder))) as $file)
				$files[strtolower(basename($file->getFileName(), '.php'))] = $file->getRealPath();
		
		return $files;
	}
	
	/**
	 * Returns the methods/events for the given class. Class can either be a Layer, the Application-Class or Data/Validator-Class
	 *
	 * @param string $class
	 * @return array
	 */
	public static function getMethods($class){
		return !empty(self::$Storage['Methods'][$class]) ? self::$Storage['Methods'][$class] : array();
	}
	
	/**
	 * Returns an array with the internal and external identifier given by the input or the default identifiers
	 *
	 * @param mixed $identifier
	 * @return array
	 */
	public static function getIdentifier($identifier = null){
		if(!$identifier)
			return self::$Storage['identifier'];
		elseif(is_scalar($identifier))
			return array(
				'internal' => $identifier,
				'external' => $identifier,
			);
		
		return array(
			'internal' => isset($identifier['internal']) ? $identifier['internal'] : self::$Storage['identifier']['internal'],
			'external' => isset($identifier['external']) ? $identifier['external'] : self::$Storage['identifier']['external'],
		);
	}
	
	/**
	 * Generates a session name for a Layer/Object-Component
	 *
	 * @param string $name
	 * @return string
	 */
	public static function generateSessionName($name){
		return '_session_'.sha1(strtolower($name).'.'.self::$Storage['secure']);
	}
	
	/**
	 * Tries to call the static method given by {@link $event} on the Application Class
	 * if even is available. This can be used at any time for any custom event
	 *
	 * <b>Predefined Events</b>
	 * <ul>
	 * <li>initialize - Gets called before the magic happens, used to set up routes, handle a logged in user etc.</li>
	 * <li>pageShow - Is called shortly before the Page outputs the html and after most of the processing is done</li>
	 * <li>packageCreate - Automatically fired on every package when it gets created to operate on the output or add additional generated code</li>
	 * </ul>
	 *
	 * @param string $event
	 * @param mixed $arg Optional argument
	 * @return mixed Returns the event return-value or false the event does not exist
	 */
	public static function fireEvent($event, $arg = null){
		static $Instance;
		if($Instance===null) $Instance = new Application;
		
		return in_array(strtolower($event), self::$Storage['Methods']['application']) ? $Instance->{'on'.$event}($arg) : false;
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
	 */
	public static function store($array, $value = null){
		if(is_scalar($array)){
			if($value) self::$Storage[$array] = $value;
			else unset(self::$Storage[$array]);
			return;
		}
		
		foreach((array)$array as $key => $value)
			if($value) self::$Storage[$key] = $value;
			else unset(self::$Storage[$key]);
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
			self::store($key, $value);
		
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
		
		for($i = 0, $l = count($args); $i<$l; $i++)
			$array[$args[$i]] = !empty(self::$Storage[$args[$i]]) ? self::$Storage[$args[$i]] : null;
		
		return $array;
	}
	
}