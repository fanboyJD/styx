<?php
class Route {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(&$get, &$post){
		
		self::initializeLayer($get['n'][0], $get['n'][1], $get, $post);
	}
	
	public static function initializeLayer($layerName, $event, &$get, &$post){
		if(!$layerName || !Core::autoload($layerName, 'Layers'))
			return false;
		
		$layerName = strtolower($layerName);
		$class = ucfirst($layerName).'Layer';
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		$layer = new $class($layerName);
		$layer->handler($event, $get, $post);
	}
}
?>