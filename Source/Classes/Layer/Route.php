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
		$routes = array();
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize($get, $post){
		if(self::$mainLayer) return;
		
		$action = $get['n'];
		$route = self::getRoute($get);
		
		if($route){
			if(!empty($route['options']['include'])){
				$file = realpath(Core::retrieve('app.path').$route['options']['include']);
				if(file_exists($file)) require($file);
			}
				
			if(!empty($route['options']['layer']))
				$action = array($route['options']['layer'], $route['options']['event']);
			
			if(!empty($route['options']['contenttype']))
				Response::setContentType($route['options']['contenttype']);
			
			$get = $route['get'];
		}
		
		if(Layer::run($action[0], $action[1], $get, $post, true))
			self::$mainLayer = $action[0];
	}
	
	public static function getMainLayer(){
		return self::$mainLayer;
	}
	
	public static function getRoute($get){
		$sep = Core::retrieve('path.separator');
		
		$routes = self::$routes;
		krsort($routes);
		
		foreach($routes as $route){
			$i = -1;
			
			foreach($route['route'] as $r){
				$i++;
				$urlpart = !empty($get['n'][$i]) ? $get['n'][$i].($get['p'][$get['n'][$i]] ? $sep.$get['p'][$get['n'][$i]] : '') : null;
				
				if(!empty($route['options']['match'][$r])){
					$m = $route['options']['match'][$r];
					
					if(!empty($m['regex']) && !preg_match($m['regex'], $urlpart))
						continue 2;
					if(!empty($m['starts']) && !String::starts($urlpart, $m['starts']))
						continue 2;
					if(!empty($m['ends']) && !String::ends($urlpart, $m['ends']))
						continue 2;
					if(!empty($m['equals']) && $urlpart!=$m['equals'])
						continue 2;
					
					if(!empty($m['as'])){
						$get['p'][$m['as']] = pick($get['p'][$get['n'][$i]], $get['n'][$i]);
						
						unset($get['p'][$get['n'][$i]]);
					}
				}elseif($urlpart!=$r){
					continue 2;
				}
			}
			
			$route['get'] = $get;
			return $route;
		}
				
		return false;
	}
	
	public static function connect($route, $options = null, $priority = 50){
		Hash::splat($options['match']);
		
		self::$routes[Data::pagetitle($priority, array(
			'contents' => array_keys(self::$routes),
		))] = array(
			'route' => explode('/', $route),
			'options' => $options
		);
	}
	
}