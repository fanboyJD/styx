<?php
class Route {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(&$get, &$post){
		
		self::initializeLayer($get['n'][0], $get['n'][1], $get, $post);
	}
	
	public static function initializeLayer($class, $event, &$get, &$post){
		if(!$class || !Core::autoload($class, 'Layers'))
			return false;
		
		$class = ucfirst(strtolower($class)).'Layer';
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		$layer = new $class();
		$layer->handler($event, $get, $post);
	}
}
?>