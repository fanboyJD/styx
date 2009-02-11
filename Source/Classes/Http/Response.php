<?php
/*
 * Styx::Response - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Sets the output-headers, cookies and whatnot
 *
 */

final class Response {

	private static /**
		 * @var ContentType
		 */
		$ContentType = null,
		$Headers = array(),
		$Types = array();
	
	public static function setHeader($array, $value = null){
		if(!is_array($array))
			$array = array($array => $value);
		
		foreach($array as $key => $value)
			if(empty(self::$Headers[$key]) || self::$Headers[$key]!=$value){
				if($value) self::$Headers[$key] = $value;
				else unset(self::$Headers[$key]);
			}
	}
	
	public static function sendHeaders(){
		if(headers_sent()) return false;
		
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
		
		$cookie = Request::retrieve('cookie');
		if($value) $cookie[$key] = $value;
		else unset($cookie[$key]);
		Request::store('cookie', $cookie);
	}
	
	public static function removeCookie($key){
		self::setCookie($key, false, time()-3600);
	}
	
	public static function link($options = null, $base = null){
		static $Configuration;	
		if(!$Configuration) $Configuration = Core::fetch('path.separator', 'app.link', 'contenttype.querystring');
		
		if(!is_array($options) && $options){
			if(!$base) return $Configuration['app.link'].$options;
			
			$options = array($options => null);
		}
		
		$array = array();
		if(!empty($options[$Configuration['contenttype.querystring']])){
			$array[] = $Configuration['contenttype.querystring'].$Configuration['path.separator'].$options[$Configuration['contenttype.querystring']];
			unset($options[$Configuration['contenttype.querystring']]);
		}
		
		if(is_array($base))
			foreach($base as $v)
				$array[] = is_array($v) ? implode($Configuration['path.separator'], $v) : $v;
		
		if(is_array($options))
			foreach($options as $k => $v)
				$array[] = $k.($v || is_numeric($v) ? $Configuration['path.separator'].$v : '');
		
		if(count($array)) return $Configuration['app.link'].implode('/', $array);
		
		return $Configuration['app.link'];
	}
	
	public static function setDefaultContentType(){
		$args = func_get_args();
		if(count($args)) self::$Types = array_map('strtolower', $args);;
		
		$class = Request::retrieve('behaviour').'content';
		
		if($exists = Core::classExists($class))
			$class = new $class;
		
		if(!$exists || !in_array($class->getType(), self::$Types) || $class->isExtended()){
			$class = Core::retrieve('contenttype.default').'content';
			$class = new $class;
		}
		
		self::$ContentType = $class;
	}
	
	public static function setContentType($contentType){
		if(is_string($contentType)){
			$class = strtolower($contentType).'content';
			if(Core::classExists($class)) $contentType = new $class;
			else return;
		}
		
		self::$ContentType = $contentType;
	}
	
	public static function getContentType(){
		return self::$ContentType ? self::$ContentType->getType() : false;
	}
	
	public static function retrieveContentType(){
		if(!self::$ContentType) self::setDefaultContentType();
		
		return self::$ContentType;
	}
	
}