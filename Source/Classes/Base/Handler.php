<?php
/*
 * Styx::Handler - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Sets the output-header and parses all data to stream it to the client
 *
 */

class Handler extends Template {
	
	private static $Instances = array(
			'master' => null,
			'slaves' => array(),
		),
		$Type = null,
		$Types = array(
			'html' => array(
				'headers' => array(
					'Content-Type' => 'text/html; charset=UTF-8',
				),
				'defaultCallback' => array('Data', 'implode'),
			),
			
			'json' => array(
				'headers' => array(
					'Content-Type' => 'application/json; charset=UTF-8',
				),
				'callback' => array('Data', 'encode', array(
					'whitespace' => 'clean',
				)),
			),
			
			'xml' => array(
				'headers' => array(
					'Content-Type' => 'application/xhtml+xml; charset=UTF-8',
				),
			),
		),
		$ExtendedTypes = array(
			'js' => array(
				'headers' => array(
					'Content-Type' => 'text/javascript; charset=UTF-8',
				),
				'callback' => array('PackageManager', 'compress'),
			),
			
			'css' => array(
				'headers' => array(
					'Content-Type' => 'text/css; charset=UTF-8',
				),
				'callback' => array('PackageManager', 'compress'),
			),
		);
	
	private $parsed = null,
		$name = null,
		$master = false,
		$base = 'Handler',
		$enabled = true,
		$substitution;
	
	protected function __construct($name = null){
		$this->name = $name;
		if(!$name) $this->master = true;
		
		$this->bind($this);
	}
	
	/**
	 * @param string $name
	 * @return Handler
	 */
	public static function map($name = null){
		if(!$name) $type = $name = 'master';
		else $type = 'slaves';
		
		if(!self::$Instances[$type][$name])
			self::$Instances[$type][$name] = new Handler($name=='master' ? null : $name);
		
		return self::$Instances[$type][$name];
	}
	
	public static function useExtendedTypes(){
		self::$Types = array_merge(self::$Types, self::$ExtendedTypes);	
	}
	
	public static function setHeader($headers = null){
		if(!$headers) $headers = self::$Types[self::$Type]['headers'];
		
		if(is_array($headers))
			foreach($headers as $k => $v)
				header($k.': '.$v);
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
	
	public static function setType($type = null){
		if(self::$Type) return;
		
		$type = strtolower($type);
		if(!self::$Types[$type]){
			reset(self::$Types);
			$type = key(self::$Types);
		}
		
		self::$Type = $type;
	}
	
	public static function setHandlers($handlers){
		foreach(self::$Types as $k => $v)
			if(!in_array($k, $handlers))
				unset(self::$Types[$k]);
		
		self::setDefaultHeaders();
	}
	
	public static function setDefaultHeaders(){
		$time = time();
		$headers = array(
			'Expires' => date('r', $time-1000000),
			'Last-Modified' => date('r', $time),
			'Cache-Control' => 'no-cache, no-store, must-revalidate'
		);
		
		foreach(self::$Types as $k => $type)
			Hash::extend(self::$Types[$k]['headers'], $headers);
	}
	
	public static function behaviour(){
		$types = Hash::args(func_get_args());
		
		return in_array(self::$Type, $types);
	}
	
	public static function remove($name, $slave = true){
		unset(self::$Instances[$slave ? 'slaves' : 'master'][$name]);
	}
	
	public static function link($options = null, $base = null){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'path.separator' => Core::retrieve('path.separator'),
				'app.link' => Core::retrieve('app.link'),
			);
		
		$array = array();
		if($options['handler']){
			$array[] = 'handler'.$Configuration['path.separator'].$options['handler'];
			unset($options['handler']);
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
	
	public function getName(){
		return $this->name;
	}
	
	/**
	 * @return Handler
	 */
	public function disable(){
		$this->enabled = false;
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function enable(){
		$this->enabled = true;
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function base(){
		$this->base = Hash::args(func_get_args());
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function template(){
		$args = Hash::args(func_get_args());
		
		foreach(array_reverse(Hash::splat($this->base)) as $v)
			array_unshift($args, $v);
		
		return $this->initialize($args);
	}
	
	/**
	 * @return Handler
	 */
	public function filter($string){
		if(is_array($string)){
			array_walk($string, array($this, 'filter'));
			
			return $this;
		}
		
		foreach($this->assigned as $k => $val)
			if(!String::starts($k, $string))
				unset($this->assigned[$k]);
		
		return $this;
	}
	
	/**
	 * Useful for JSON-Handler: This Method sets a key for later substitution. Only the
	 * assigned variable with the given key will be send to output and it will replace
	 * the original assignment array. 
	 * 
	 * @return Handler
	 */
	public function substitute($key){
		$this->substitution = $key;
		
		return $this;
	}
	
	public function callback($callback = 'callback'){
		$c = self::$Types[self::$Type][$callback];
		$class = is_array($c);
		if($c && (($class && method_exists($c[0], $c[1])) || function_exists($c)))
			$this->parsed = call_user_func_array($class ? array($c[0], $c[1]) : $c, array($this->assigned, $class ? $c[2] : null));
	}
	
	public function parse($return = false){
		if(!$this->enabled)
			return;
		
		if($this->master){
			foreach(self::$Instances['slaves'] as $k => $v)
				$assign[$k] = $v->parse(true);
			
			$main = Route::getMainLayer();
			if($main) $assign['layer'] = $assign['layer.'.$main];
			
			$this->assign($assign);
		}
		
		if($this->substitution) $this->assigned = $this->assigned[$this->substitution];
		
		if($this->master) $this->callback();
		else $this->parsed = $this->assigned;
		
		if(count($this->file)){
			if($this->master && is_array($this->parsed)) $this->assign($this->parsed);
			
			return parent::parse($return);
		}else{
			$this->callback('defaultCallback');
		}
		
		if($return) return $this->parsed;
		
		echo $this->parsed;
		flush();
	}
	
}