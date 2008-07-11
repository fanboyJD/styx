<?php
class Route {
	
	private static $mainLayer = null,
		$rules = array();
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize($get, $post){
		if(self::$mainLayer) return;
		
		$action = $get['n'];
		$route = self::getRoute($get);
		
		if($route){
			if(is_array($route['action'])){
				/* We overwrite the get and post values here if needed */
				$tmp = array(
					'n' => $get['n'],
					'p' => $get['p'],
					'm' => $get['m'],
				);
				
				foreach(array(
					2 => 'get',
					3 => 'post',
				) as $k => $v)
					if(is_array($route['action'][$k])){
						$$v = $route['action'][$k];
						unset($route['action'][$k]);
					}
				
				/* We map the routed values to the original ones */
				$get = array_merge($get, $tmp);
				for($i=0;$i<=1;$i++){
					$val = $route['map'][$i][1] ? $route['map'][$i][0] : $get['p'][$route['map'][$i][0] ? $route['map'][$i][0] : $get['n'][$i]];
					
					$get['p'][$route['action'][$i]] = $val;
				}
				
				$action = $route['action'];
			}else{
				$file = realpath(Core::retrieve('app.path').$route['action']);
				if(file_exists($file)) include($file);
			}
		}
		
		if(Layer::run($action[0], $action[1], $get, $post, true))
			self::$mainLayer = $action[0];
	}
	
	public static function getMainLayer(){
		return self::$mainLayer;
	}
	
	public static function getRoute($get){
		$rules = self::$rules;
		krsort($rules);
		
		foreach($rules as $rule){
			$pass = false;
			for($i=0,$length=sizeof($rule['route']);$i<$length;$i++){
				$route = Hash::splat($rule['route'][$i]);
				if(!$route['match']) $route['match'] = $route[0];
				if(!$route['type']) $route['type'] = $route[1];
				
				if(
					((!$route['type'] || $route['type']=='equals') && $get['n'][$i]==$route['match'])
					||
					($route['type']=='startsWith' && startsWith($get['n'][$i], $route['match']))
					||
					($route['type']=='regex' && preg_match('/'.$route['match'].'/', $get['n'][$i]))
				){
					if(key_exists('pass', $route)) $rule['map'][$route['pass']] = array($get['n'][$i], $route['passAll']);
					$pass = true;
				}else{
					$pass = false;
				}
			}
			
			if($pass) return $rule;
		}
				
		return false;
	}
	
	public static function connect($route, $action, $priority = 50){
		$route = Hash::splat($route);
		
		self::$rules[Data::pagetitle($priority, array(
			'contents' => array_keys(self::$rules),
		))] = array(
			'route' => $route,
			'action' => $action
		);
	}
	
}