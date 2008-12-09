<?php
/*
 * Styx::Response - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Sets the output-headers, cookies and whatnot
 *
 */

class Response {

	private static /**
		 * @var ContentType
		 */
		$ContentType = null,
		$Headers = array(),
		$Types = array();
	
	public static function setHeader($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				self::setHeader($k, $val);
			
			return $key;
		}
		
		if(empty(self::$Headers[$key]) || self::$Headers[$key]!=$value){
			self::$Headers[$key] = $value;
			if(!$value) unset(self::$Headers[$key]);
		}
	}
	
	public static function sendHeaders(){
		$headers = self::$ContentType->getHeaders();
		foreach(Hash::extend($headers, self::$Headers) as $key => $value)
			header($key.': '.$value);
	}
	
	public static function setCookie($key, $value, $expire = null){
		static $Configuration;
		
		if(!$Configuration){
			$Configuration = Core::retrieve('cookie');
			$Configuration['expire'] += time();
			foreach(array('path', 'domain', 'secure', 'httponly') as $v)
				if(empty($Configuration[$v]))
					$Configuration[$v] = null;
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
	

	
	public static function link($options = null, $base = null){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'path.separator' => Core::retrieve('path.separator'),
				'app.link' => Core::retrieve('app.link'),
				'handler' => Core::retrieve('contenttype.querystring'),
			);
		
		if(!is_array($options) && $options){
			$wrapper[$options] = null;
			$options = $wrapper;
		}
		
		$array = array();
		if(!empty($options[$Configuration['handler']])){
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
	
	public static function retrieveContentType(){
		if(!self::$Types || !self::$ContentType || (self::$ContentType && !in_array(self::getContentType(), self::$Types)))
			self::setDefaultContentType();
		
		return self::$ContentType;
	}
	
	public static function allow(){
		$args = Hash::args(func_get_args());
		
		self::$Types = array_map('strtolower', $args);
	}
	
}