<?php

class Request extends StaticStorage {
	
	private static $method = null,
		$client = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(){
		self::$method = strtolower($_SERVER['REQUEST_METHOD']);
		
		self::parse();
	}
	
	public static function getMethod(){
		return self::$method;
	}
	
	public static function parse(){
		$polluted = array();
		
		$vars = explode('/', $_SERVER['PATH_INFO']);
		array_shift($vars);
		
		$version = Core::retrieve('app.version');
		$separator = Core::retrieve('path.separator');
		
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
			
			$polluted['p'][$v[0]] = pick($v[1]);
			if($v[0]!='handler') $polluted['n'][] = $v[0];
		}
		
		foreach(array('index', 'view') as $k => $v)
			if(!$polluted['n'][$k]){
				$polluted['n'][$k] = $v;
				$polluted['p'][$v] = null;
			}
		
		if(!$polluted['p']['handler']) $polluted['p']['handler'] = 'html';
		
		
		$get = array_merge(self::sanitize($_GET), $polluted);
		if(Hash::length($get)) self::store('get', $get);
		
		foreach(array('post', 'cookie') as $v)
			self::store($v, self::sanitize($GLOBALS['_'.strtoupper($v)]));
	}
	
	public static function sanitize($data){
		$data = Data::clean($data);
		
		if(Hash::length($data)){
			if(get_magic_quotes_gpc())
				foreach($data as &$val)
					$val = stripslashes($val);
			
			return $data;
		}
		
		return array();
	}
	
	public static function getClient(){
		if(is_array(self::$client)) return self::$client;
		
		$client = $_SERVER['HTTP_USER_AGENT'];
		
		if(preg_match('/msie ([0-9]).*[0-9]*b*;/i', $client, $m)){
			self::$client = array(
				'browser' => 'ie',
				'version' => $m[1][0],
			);
			
			if(strpos($client, 'SV1')!==false)
				self::$client['features']['servicePack'] = true;
		}else{
			self::$client = array(
				'browser' => 'compatible',
			);
		}
		
		return self::$client;
	}
	
}