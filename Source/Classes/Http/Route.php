<?php
/*
 * Styx::Route - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Checks whether to connect to a certain file or layer
 *
 */

class Route {
	
	private static $mainlayer = null,
		$routes = array(),
		$hidden = array();
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(){
		if(self::$mainlayer) return;
		
		$action = Request::fetch('get', 'layer', 'event', 'parts', 'keys');
		
		$route = self::getRoute($action);
		
		if($route){
			if(!empty($route['options']['contenttype']))
				Response::setContentType($route['options']['contenttype']);

			if(!empty($route['options']['package']))
				PackageManager::showPackage($route['options']['package']); // We won't get past this line if it matches
			
			if(!empty($route['options']['include'])){
				$file = realpath(Core::retrieve('app.path').$route['options']['include']);
				if(file_exists($file)) require($file);
			}
				
			if(!empty($route['options']['layer'])){
				$action['layer'] = $route['options']['layer'];
				$action['event'] = !empty($route['options']['event']) ? $route['options']['event'] : null;
			}
			
			if(!empty($route['layer'])) $action['layer'] = $route['layer'];
			if(!empty($route['event'])) $action['event'] = $route['event'];
			
			$action['get'] = $route['get'];
		}elseif(in_array(array( // If there is no route and the layer/event is hidden we do not execute it :)
			'layer' => $action['layer'],
			'event' => $action['event'],
		), self::$hidden) || in_array(array(
			'layer' => $action['layer'],
			'event' => '*',
		), self::$hidden))
			return;
		
		if(!empty($route['options']['preventDefault']))
			return;
		
		$layer = Layer::retrieve($action['layer']);
		if(!$layer) return;
		
		$layer->setMainLayer()->fireEvent($action['event'], !empty($action['get']) ? $action['get'] : null)->register();
		self::$mainlayer = String::toLower($action['layer']);
	}
	
	public static function getMainlayer(){
		return self::$mainlayer;
	}
	
	public static function getRoute($action){
		$path = Request::getPath();
		
		$routes = self::$routes;
		krsort($routes);
		
		foreach($routes as $route){
			$i = -1;
			if(!empty($route['options']['equals'])){
				if($route['route']!=$path)
					continue;
			}elseif(!empty($route['options']['regex'])){
				if(!preg_match($route['route'], $path))
					continue;
			}else{
				foreach($route['route'] as $r){
					$i++;
					
					if(!empty($route['options']['match'][$r]) && !empty($action['parts'][$i])){
						$m = $route['options']['match'][$r];
						
						if(!empty($m['regex']) && !preg_match($m['regex'], $action['parts'][$i]))
							continue 2;
						if(!empty($m['starts']) && !String::starts($action['parts'][$i], $m['starts']))
							continue 2;
						if(!empty($m['ends']) && !String::ends($action['parts'][$i], $m['ends']))
							continue 2;
						if(!empty($m['equals']) && $action['parts'][$i]!=$m['equals'])
							continue 2;
						
						if(!empty($action['keys'][$i])){
							if(!empty($m['as'])){
								$action['get'][$m['as']] = pick($action['get'][$action['keys'][$i]], $action['keys'][$i]);
								
								unset($action['get'][$action['keys'][$i]]);
							}
							
							if(!empty($m['layer'])) $route['layer'] = $action['keys'][$i];
							if(!empty($m['event'])) $route['event'] = $action['keys'][$i];
						}
					}elseif(empty($route['options']['match'][$r]['omit']) && (empty($action['parts'][$i]) || $action['parts'][$i]!=$r)){
						continue 2;
					}
				}
			}
			
			$route['get'] = $action['get'];
			
			return $route;
		}
				
		return false;
	}
	
	public static function connect($route, $options = array(), $priority = 50){
		Hash::splat($options['match']);
		
		self::$routes[Data::pagetitle($priority, array(
			'contents' => array_keys(self::$routes),
		))] = array(
			'route' => !empty($options['regex']) || !empty($options['equals']) ? $route : Data::nullify(explode('/', $route)),
			'options' => $options
		);
	}
	
	public static function hide($layer, $event = null){
		self::$hidden[]  = array(
			'layer' => String::toLower($layer),
			'event' => pick(String::toLower($event), '*'),
		);
	}
	
}