<?php
/*
 * Styx::Request - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses HTTP-Headers, provides data send by the user and holds URL-Information
 *
 */

final class Request {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(){
		$method = isset($_SERVER['REQUEST_METHOD']) ? String::toLower($_SERVER['REQUEST_METHOD']) : null;
		
		self::store('method', in_array($method, array('get', 'post', 'put', 'delete')) ? $method : 'get');
		
		self::parse();
	}
	
	public static function parse(){
		foreach(array('post', 'cookie') as $v)
			self::store($v, self::sanitize($GLOBALS['_'.String::toUpper($v)]));
		
		$request = self::processRequest();
		
		if(!empty($request['language']) && Core::retrieve('languages.cookie'))
			Response::setCookie(Core::retrieve('languages.cookie'), $request['language']);
		
		self::store($request);
	}
	
	public static function processRequest($path = null){
		static $Configuration, $processed = array();
		
		if(!$Configuration){
			$Configuration = Core::fetch(
				'path.separator', 'languages.querystring', 'languages',
				'contenttype.querystring', 'contenttype.default', 'layer.default'
			);
			
			$Configuration['checks'] = array();
			
			$check = array('behaviour' => 'contenttype.querystring');
			if(Hash::length($Configuration['languages']))
				$check['language'] = 'languages.querystring';
			
			foreach($check as $k => $v)
				if($Configuration[$v])
					$Configuration['checks'][$k] = $Configuration[$v];
		}
		
		$path = pick($path, isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
		
		if(!empty($processed[$path])) return $processed[$path];
		
		foreach(array('get', 'request', 'parts', 'keys') as $v)
			$request[$v] = array();
		
		$vars = explode('/', $path);
		array_shift($vars);
		
		$i = 0;
		for($k = 0, $l = count($vars); $k<$l; $k++){
			$v = String::clean($vars[$k]);
			if(!$v) continue;
			
			$v = explode($Configuration['path.separator'], $v, 2);
			if(empty($v[0]) || !empty($request['get'][$v[0]])) continue;
			
			if($e = !empty($v[1])){
				foreach($Configuration['checks'] as $key => $value){
					if($v[0]==$value){
						$request[$key] = $v[1];
						continue 2;
					}
				}
			}
			
			$request['parts'][] = $vars[$k];
			$request['keys'][] = $v[0];
			$request['get'][$v[0]] = $e ? $v[1] : null;
			$request['request'][Data::sanitize($v[0])] = $e ? Data::sanitize($v[1]) : null;
			
			if($i<2) $request[$i++ ? 'event' : 'layer'] = $v[0];
		}
		
		foreach(array('layer', 'event') as $v)
			if(empty($request[$v]))
				$request[$v] = $Configuration['layer.default'][$v];
		
		if(empty($request['behaviour'])) $request['behaviour'] = $Configuration['contenttype.default'];
		
		return $processed[$path] = $request;
	}
	
	public static function sanitize($data){
		$data = String::clean($data);
		
		if(Hash::length($data)){
			if(get_magic_quotes_gpc())
				$data = array_map('stripslashes', $data);
			
			return String::convert($data);
		}
		
		return array();
	}
	
	public static function getClient(){
		static $client;
		
		if(!$client){
			$uagent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
			
			if(preg_match('/msie ([0-9]).*[0-9]*b*;/i', $uagent, $m)){
				$client = array(
					'browser' => 'ie',
					'version' => $m[1][0],
				);
				
				if(strpos($uagent, 'SV1')!==false)
					$client['features']['servicePack'] = true;
			}else{
				$client = array(
					'browser' => 'compatible',
				);
			}
		}
		
		return $client;
	}
	
	public function getUrl(){
		static $url;
		
		if(!$url){
			$url = self::getProtocol().'://'.self::getServer().self::getScript().self::getPath();
			
			if(!String::ends($url, '/')) $url .= '/';
		}
		
		return $url;
	}
	
	public static function isSecure(){
		return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
	}
	
	public static function getReferer(){
		return !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}
	
	public function getHost(){
		return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
	}
	
	public function getPort(){
		return !empty($_SERVER['SERVER_PORT']) ? pick(Data::id($_SERVER['SERVER_PORT']), 80) : 80;
	}
	public function getServer(){
		if(empty($_SERVER['SERVER_NAME'])) return;
		
		$port = self::getPort();
		
		return $_SERVER['SERVER_NAME'].($port!=80 && $port ? ':'.$port : '');
	}
	
	public function getProtocol(){
		return 'http'.(self::isSecure() ? 's' : '');
	}
	
	public function getPath(){
		static $Configuration, $path;
		
		if(!$Configuration)
			$Configuration = Core::fetch('path.separator');
		
		if($path) return $path;
		
		$path = array();
		foreach(Hash::splat(self::retrieve('request')) as $k => $v)
			$path[] = $k.($v ? $Configuration['path.separator'].$v : '');
		
		return $path = implode('/', $path);
	}
	
	public function getScript(){
		return !empty($_SERVER['SCRIPT_NAME']) ? pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).'/' : '';
	}

	public static function getLanguage(){
		static $lang;
		
		if(!$lang){
			$languages = Core::retrieve('languages');
			
			if(count($languages)==1)
				return key($languages);
			
			foreach(self::getRequestedLanguages() as $langs)
				foreach($languages as $k => $language)
					if(in_array($langs, $language))
						return $lang = $k;
			
			reset($languages);
			return key($languages);
		}
		
		return $lang;
	}
	
	public static function getRequestedLanguages(){
		static $langs;
		
		if(!$langs){
			if($languagescookie = Core::retrieve('languages.cookie')){
				$cookie = self::retrieve('cookie');
				if(!empty($cookie[$languagescookie]))
					$langs[Data::pagetitle($cookie[$languagescookie])] = 2; // We strip out bad content
			}
			
			if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				return $langs = array();
			
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $m);
			
			if(count($m[1])){
				$langs = array_merge(array_combine($m[1], $m[4]), Hash::splat($langs));
				foreach($langs as $lang => $val)
					if(!$val && !is_numeric($val))
						$langs[$lang] = 1;
				
				arsort($langs, SORT_NUMERIC);
				$langs = array_unique(array_keys($langs));
			}
		}
		
		return $langs;
	}
	
	/* Storage Methods (Will be moved to a StaticStorage-Class in PHP5.3) */
	private static $Storage = array();
	
	public static function store($array, $value = null){
		if(!is_array($array)){
			if($value) self::$Storage[$array] = $value;
			else unset(self::$Storage[$array]);
			return;
		}
		
		foreach($array as $key => $value)
			if($value) self::$Storage[$key] = $value;
			else unset(self::$Storage[$key]);
	}
	
	public static function retrieve($key, $value = null){
		if($value && empty(self::$Storage[$key]))
			self::store($key, $value);
		
		return !empty(self::$Storage[$key]) ? self::$Storage[$key] : null;
	}
	
	public static function fetch(){
		$args = Hash::args(func_get_args());
		$array = array();
		
		for($i = 0, $l = count($args); $i<$l; $i++)
			$array[$args[$i]] = !empty(self::$Storage[$args[$i]]) ? self::$Storage[$args[$i]] : null;
		
		return $array;
	}
	
}