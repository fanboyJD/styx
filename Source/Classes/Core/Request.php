<?php
/*
 * Styx::Request - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses HTTP-Headers, provides data send by the user and holds URL-Information
 *
 */

class Request extends Storage {
	
	private static $method;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(){
		self::$method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
		
		if(!in_array(self::$method, array('get', 'post', 'put', 'delete'))) self::$method = 'get';
		
		self::getInstance()->parse();
	}
	
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Request();
	}
	
	public function parse(){
		$polluted = self::processRequest();
			
		foreach(array('post', 'cookie') as $v)
			$this->store($v, self::sanitize($GLOBALS['_'.strtoupper($v)]));
		
		if(!empty($polluted['m']['language']))
			Page::setCookie(Core::retrieve('languages.cookie'), $polluted['m']['language']);
		
		$get = array_merge(self::sanitize($_GET), $polluted);
		if(Hash::length($get)) $this->store('get', $get);
	}
	
	public static function processRequest($path = null){
		static $Configuration;
		
		if(!$Configuration)
			$Configuration = array(
				'version' => Core::retrieve('app.version'),
				'separator' => Core::retrieve('path.separator'),
				'language' => Core::retrieve('languages.querystring'),
				'handler' => Core::retrieve('contenttype.querystring'),
				'default' => Core::retrieve('contenttype.default'),
			);
		
		$polluted = array();
		foreach(array('n', 'p', 'm', 'o') as $v)
			$polluted[$v] = array();
		
		$vars = explode('/', pick($path, isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''));
		array_shift($vars);
		
		foreach($vars as $k => $v){
			$v = Data::clean($v);
			if(!$v) continue;
			
			$v = explode($Configuration['separator'], $v, 2);
			if(!empty($polluted['p'][$v[0]])) continue;
			
			if($Configuration['version'] && !$k && $Configuration['version']==$v[0] && strpos($vars[$k+1], '.')){
				$polluted['m']['package'] = $vars[$k+1];
				continue;
			}elseif($Configuration['language'] && $v[0]==$Configuration['language'] && $v[1]){
				$polluted['m']['language'] = $v[1];
				continue;
			}
			
			$polluted['p'][$v[0]] = isset($v[1]) ? pick($v[1]) : null;
			if($v[0]!=$Configuration['handler']) $polluted['n'][] = $v[0];
		}
		
		$polluted['o'] = $polluted['p']; // "Original"
		foreach(array('index', 'view') as $k => $v)
			if(empty($polluted['n'][$k])){
				$polluted['n'][$k] = $v;
				$polluted['p'][$v] = null;
			}
		
		if($Configuration['handler'] && empty($polluted['p'][$Configuration['handler']]))
			$polluted['p'][$Configuration['handler']] = $Configuration['default'];
		
		return $polluted;
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
	
	public static function isSecure(){
		return $_SERVER['HTTPS'] && $_SERVER['HTTPS'] == 'on';
	}
	
	public static function getReferer(){
		return pick($_SERVER['HTTP_REFERER']);
	}
	
	public function getUrl(){
		static $url;
		
		if(!$url){
			$url = self::getProtocol().'://'.self::getServer().self::getPath();
			
			if(!String::ends($url, '/')) $url .= '/';
		}
		
		return $url;
	}
	
	public function getHost(){
		return pick($_SERVER['HTTP_HOST']);
	}
	
	public function getPort(){
		return pick(Data::id($_SERVER['SERVER_PORT']), 80);
	}
	public function getServer(){
		$port = self::getPort();
		
		return $_SERVER['SERVER_NAME'].($port!=80 && $port ? ':'.$port : '');
	}
	
	public function getProtocol(){
		return 'http'.(self::isSecure() ? 's' : '');
	}
	
	public function getPath(){
		return $_SERVER['REQUEST_URI'];
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
				$cookie = self::getInstance()->retrieve('cookie');
				if($cookie[$languagescookie])
					$langs[$cookie[$languagescookie]] = 2;
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
	
	public static function behaviour(){
		$types = Hash::args(func_get_args());
		
		return in_array(self::getBehaviour(), $types);
	}
	
	public static function getBehaviour(){
		static $Configuration;
		
		if(!$Configuration)
			$Configuration = array(
				'handler' => Core::retrieve('contenttype.querystring'),
			);
		
		if(!$Configuration) return false;
		
		$get = Request::getInstance()->retrieve('get');
		return $get['p'][$Configuration['handler']];
	}
	
}