<?php
/*
 * Styx::Page - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Sets the output-headers and parses all data to stream it to the client
 *
 */

class Page extends Template {
	
	private static /**
		 * @var ContentType
		 */
		$ContentType = null,
		$Headers = array(),
		$Types = array(),
		$Templates = array();
	
	private $substitution = null;
	
	protected function __construct(){
		$this->base = 'Page';
		
		$time = time();
		self::setHeader(array(
			'Expires' => date('r', $time-1000),
			'Last-Modified' => date('r', $time),
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
		));
		
		$this->bind($this);
	}
	
	/**
	 * @return Page
	 */
	public static function map(){
		return self::getInstance();
	}
	
	/**
	 * 
	 * Using getInstance is more intuitive than map
	 * 
	 * @return Page
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Page();
	}
	
	public static function setHeader($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				self::setHeader($k, $val);
			
			return $key;
		}
		
		if(!self::$Headers[$key] || self::$Headers[$key]!=$value){
			self::$Headers[$key] = $value;
			if(!$value) unset(self::$Headers[$key]);
		}
	}
	
	public static function setCookie($key, $value, $expire = null){
		static $Configuration;
		
		if(!$Configuration){
			$Configuration = Core::retrieve('cookie');
			$Configuration['expire'] += time();
		}
		
		setcookie($key, $value, pick($expire, $Configuration['expire']), $Configuration['path'], $Configuration['domain'], $Configuration['secure'], $Configuration['httponly']);
		
		$cookie = Request::getInstance()->retrieve('cookie');
		if($value) $cookie[$key] = $value;
		else unset($cookie[$key]);
		Request::getInstance()->store('cookie', $cookie);
	}
	
	public static function removeCookie($key){
		self::setCookie($key, false, time()-3600);
	}
	
	public static function setDefaultContentType(){
		$args = Hash::args(func_get_args());
		if(Hash::length($args)) self::allow($args);
		
		$class = Request::getBehaviour().'content';
		
		if($exists = Core::classExists($class))
			$class = new $class;
		
		if(!$exists || !in_array($class->getType(), self::$Types) || $class->isExtended()){
			$class = Core::retrieve('contenttype.default').'content';
			$class = new $class;
		}
		
		self::setContentType(new $class);
	}
	
	public static function setContentType($contentType){
		if(is_string($contentType)){
			$class = strtolower($contentType).'content';
			if(Core::classExists($class)) $contentType = new $class;
			else return;
		}
		
		if(!in_array($contentType->getType(), self::$Types)) return;
		
		self::$ContentType = $contentType;
	}
	
	public static function getContentType(){
		return self::$ContentType ? self::$ContentType->getType() : false;
	}
	
	public static function allow(){
		$args = Hash::args(func_get_args());
		
		self::$Types = array_map('strtolower', $args);
	}
	
	public static function register($name, $obj){
		self::$Templates[$name] = $obj;
	}
	
	public static function deregister($name){
		unset(self::$Templates[$name]);
	}
	
	public static function link($options = null, $base = null){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'path.separator' => Core::retrieve('path.separator'),
				'app.link' => Core::retrieve('app.link'),
				'handler' => Core::retrieve('contenttype.querystring'),
			);
		
		$array = array();
		if($options[$Configuration['handler']]){
			$array[] = $Configuration['handler'].$Configuration['path.separator'].$options['handler'];
			unset($options[$Configuration['handler']]);
		}
		
		if(Hash::length(Hash::splat($base)))
			foreach($base as $v)
				$array[] = is_array($v) ? implode($Configuration['path.separator'], $v) : $v;
		
		if(Hash::length($options))
			foreach($options as $k => $v)
				$array[] = $k.($v ? $Configuration['path.separator'].$v : '');
		
		if(count($array)) $array = implode('/', $array);
		else $array = null;
		
		return $Configuration['app.link'].$array;
	}
	
	/**
	 * Useful for JSON: This Method sets a key for later substitution. Only the
	 * assigned variable with the given key will be send to output and it will replace
	 * the original assignment array. 
	 * 
	 * @return Page
	 */
	public function substitute($key){
		$this->substitution = $key;
		
		return $this;
	}
	
	public function show($return = false){
		if(!self::$Types || !self::$ContentType || (self::$ContentType && !in_array(self::getContentType(), self::$Types)))
			self::setDefaultContentType();
		
		foreach(self::$Templates as $k => $v)
			$assign[$k] = $v->parse(true);
		
		$main = Route::getMainLayer();
		if($main && $assign['layer.'.$main]) $assign['layer'] = $assign['layer.'.$main];
		
		$this->assign($assign);
		
		if($this->substitution) $this->assigned = $this->assigned[$this->substitution];
		
		$out = self::$ContentType->process($this->assigned);
		
		if(count($this->file)){
			$this->assigned = $out;
			
			$out = parent::parse(true);
		}
		
		$headers = self::$ContentType->getHeaders();
		foreach(Hash::extend($headers, self::$Headers) as $key => $value)
			header($key.': '.$value);
		
		if($return) return $out;
		
		echo $out;
		flush();
	}
	
}