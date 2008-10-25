<?php
/*
 * Styx::Route - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Checks whether to connect to a certain file or layer
 *
 */

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
			if(!is_array($route['action']) && $route['action']){
				$file = realpath(Core::retrieve('app.path').$route['action']);
				if(file_exists($file)) require_once($file);
			}else{
				/* We overwrite the get and post values here if needed */
				if(is_array($route['action'])){
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
				
					$get = array_merge($get, $tmp);
				}
				
				/* We map the original values to the routed ones */
				for($i=0;$i<=1;$i++){
					$val = $route['map'][$i][1] ? $route['map'][$i][0] : $get['p'][$route['map'][$i][0] ? $route['map'][$i][0] : $get['n'][$i]];
					
					if($route['action'][$i]) $get['p'][$route['action'][$i]] = $val;
					else $action[$i] = $val;
				}
				
				if($route['action']) $action = $route['action'];
			}
		}
		
		if(Layer::run($action[0], $action[1], $get, $post, true))
			self::$mainLayer = $action[0];
	}
	
	public static function getMainLayer(){
		return self::$mainLayer;
	}
	
	public static function getRoute($get){
		$sep = Core::retrieve('path.separator');
		
		$rules = self::$rules;
		krsort($rules);
		
		foreach($rules as $rule){
			$pass = false;
			for($i=0,$length=sizeof($rule['route']);$i<$length;$i++){
				$route = Hash::splat($rule['route'][$i]);
				if(!$route['match']) $route['match'] = $route[0];
				if(!$route['type']) $route['type'] = $route[1];
				
				$urlpart = $get['n'][$i].($get['p'][$get['n'][$i]] ? $sep.$get['p'][$get['n'][$i]] : '');
				
				if(
					!$route['match']
					||
					((!$route['type'] || $route['type']=='equals') && $get['n'][$i]==$route['match'])
					||
					(($route['type']=='equalsAll') && $urlpart==$route['match'])
					||
					($route['type']=='startsWith' && String::starts($urlpart, $route['match']))
					||
					($route['type']=='regex' && preg_match('/'.$route['match'].'/', $urlpart))
				){
					if(key_exists('pass', $route)) $rule['map'][$route['pass']] = array($get['n'][$i], $route['passKey']);
					$pass = true;
				}else{
					$pass = false;
					break;
				}
			}
			
			if($pass) return $rule;
		}
				
		return false;
	}
	
	public static function connect($route, $action = null, $priority = 50){
		self::$rules[Data::pagetitle($priority, array(
			'contents' => array_keys(self::$rules),
		))] = array(
			'route' => Hash::splat($route),
			'action' => $action
		);
	}
	
}