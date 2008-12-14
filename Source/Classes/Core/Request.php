<?php
/*
 * Styx::Request - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses HTTP-Headers, provides data send by the user and holds URL-Information
 *
 */

class Request {
	
	private static $method, $behaviour;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(){
		self::$method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
		
		if(!in_array(self::$method, array('get', 'post', 'put', 'delete'))) self::$method = 'get';
		
		self::parse();
	}
	
	public static function parse(){
		$polluted = self::processRequest();
			
		foreach(array('post', 'cookie') as $v)
			self::store($v, self::sanitize($GLOBALS['_'.strtoupper($v)]));
		
		if(!empty($polluted['m']['language']))
			Response::setCookie(Core::retrieve('languages.cookie'), $polluted['m']['language']);
		
		$get = array_merge(self::sanitize($_GET), $polluted);
		if(Hash::length($get)) self::store('get', $get);
	}
	
	public static function processRequest($path = null){
		static $Configuration, $processed = array(), $empty;
		
		if(!$Configuration)
			$Configuration = Core::fetch(
				'app.version', 'path.separator', 'languages.querystring',
				'contenttype.querystring', 'contenttype.default', 'layer.default'
			);
		
		if(!$empty)
			foreach(array('m', 'n', 'o', 'p') as $v)
				$empty[$v] = array();
		
		$path = pick($path, isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
		
		if(!empty($processed[$path])) return $processed[$path];
		
		$polluted = $empty;
		
		$vars = explode('/', $path);
		array_shift($vars);
		
		foreach($vars as $k => $v){
			$v = Data::clean($v);
			if(!$v) continue;
			
			$v = explode($Configuration['path.separator'], $v, 2);
			if(!empty($polluted['p'][$v[0]])) continue;
			
			if($Configuration['app.version'] && !$k && $Configuration['app.version']==$v[0] && strpos($vars[$k+1], '.')){
				$polluted['m']['package'] = $vars[$k+1];
				continue;
			}elseif($Configuration['languages.querystring'] && $v[0]==$Configuration['languages.querystring'] && $v[1]){
				$polluted['m']['language'] = $v[1];
				continue;
			}
			
			if($v[0]==$Configuration['contenttype.querystring']){
				self::$behaviour = pick($v[1]);
				continue;
			}
			$polluted['p'][$v[0]] = isset($v[1]) ? pick($v[1]) : null;
			$polluted['n'][] = $v[0];
		}
		
		foreach($polluted['p'] as $k => $v)
			$polluted['o'][Data::specialchars($k)] = $v ? Data::specialchars($v) : null; // "Original" (but safe)
		
		foreach($Configuration['layer.default'] as $k => $v)
			if(empty($polluted['n'][$k])){
				$polluted['n'][$k] = $v;
				$polluted['p'][$v] = null;
			}
		
		if(!self::$behaviour)
			self::$behaviour = $Configuration['contenttype.default'];
		
		return $processed[$path] = $polluted;
	}
	
	public static function sanitize($data){
		$data = Data::clean($data);
		
		if(Hash::length($data)){
			if(get_magic_quotes_gpc())
				$data = array_map('stripslashes', $data);
			
			return $data;
		}
		
		return array();
	}
	
	public static function getMethod(){
		return self::$method;
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
			$url = self::getProtocol().'://'.self::getServer().self::getPath();
			
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
		
		$request = self::processRequest();
		
		$path = array();
		foreach($request['o'] as $k => $v)
			$path[] = $k.($v ? $Configuration['path.separator'].$v : '');
		
		return $path = (!empty($_SERVER['SCRIPT_NAME']) ? pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME).'/' : '').implode('/', $path);
	}

	public static function getLanguage(){
		static $lang;
		
		if(!$lang){
			$languages = Core::retrieve('languages');
			
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
	
	public static function setBehaviour($behaviour){
		self::$behaviour = $behaviour;
	}
	
	public static function getBehaviour(){
		return self::$behaviour;
	}
	
	/* Storage Methods (Will be moved to a StaticStorage-Class in PHP5.3) */
	private static $Storage = array();
	
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
	
	public static function retrieve($key, $value = null){
		if($value && empty(self::$Storage[$key]))
			return self::store($key, $value);
		
		return !empty(self::$Storage[$key]) ? self::$Storage[$key] : null;
	}
	
}