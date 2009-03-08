<?php
/*
 * Styx::Route - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Checks whether to connect to a certain file or layer
 *
 */

final class Route {
	
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
		self::$mainlayer = strtolower($action['layer']);
	}
	
	public static function getMainlayer(){
		return self::$mainlayer;
	}
	
	public static function getRoute($action){
		$path = Request::getPath();
		
		usort(self::$routes, array(new DataComparison('priority', -1), 'sort'));
		for($i = 0, $l = count(self::$routes); $i<$l; $i++){
			$j = -1;
			$route = self::$routes[$i];
			if(isset($route['options']['equals'])){
				if($route['route']!=$path)
					continue;
			}elseif(isset($route['options']['regex'])){
				if(!preg_match($route['route'], $path))
					continue;
			}else{
				foreach($route['route'] as $r){
					$j++;
					
					if(isset($route['options']['match'][$r]) && isset($action['parts'][$j])){
						$m = $route['options']['match'][$r];
						
						if(isset($m['regex']) && !preg_match($m['regex'], $action['parts'][$j]))
							continue 2;
						if(isset($m['starts']) && !String::starts($action['parts'][$j], $m['starts']))
							continue 2;
						if(isset($m['ends']) && !String::ends($action['parts'][$j], $m['ends']))
							continue 2;
						if(isset($m['equals']) && $action['parts'][$j]!=$m['equals'])
							continue 2;
						
						if(isset($action['keys'][$j])){
							if(isset($m['as'])){
								$action['get'][$m['as']] = pick($action['get'][$action['keys'][$j]], $action['keys'][$j]);
								
								unset($action['get'][$action['keys'][$j]]);
							}
							
							if(isset($m['layer'])) $route['layer'] = $action['keys'][$j];
							if(isset($m['event'])) $route['event'] = $action['keys'][$j];
						}
					}elseif(empty($route['options']['match'][$r]['omit']) && (empty($action['parts'][$j]) || $action['parts'][$j]!=$r)){
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
		self::$routes[] = array(
			'route' => empty($options['regex']) && empty($options['equals']) ? explode('/', $route) : $route,
			'options' => $options,
			'priority' => $priority,
		);
	}
	
	public static function hide($layer, $event = null){
		self::$hidden[] = array(
			'layer' => strtolower($layer),
			'event' => pick(strtolower($event), '*'),
		);
	}
	
}