<?php

class Request extends StaticStorage {
	
	private static $method = null;
	
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
			
			$polluted['p'][$v[0]] = pick($v[1], null);
			if($v[0]!='handler') $polluted['n'][] = $v[0];
		}
		
		foreach(array('index', 'view') as $k => $v)
			if(!$polluted['n'][$k]){
				$polluted['n'][$k] = $v;
				$polluted['p'][$v] = null;
			}
		
		if(!$polluted['p']['handler']) $polluted['p']['handler'] = 'html';
		
		unset($_GET['n'], $_GET['p'], $_GET['m']);
		self::store('get', array_merge($_GET, $polluted));
		
		$post = $_POST;
		if(self::getMethod()=='post' && sizeof($post) && get_magic_quotes_gpc())
			foreach($post as &$val)
				$val = stripslashes($val);
		
		self::store('post', $post);
	}
	
}