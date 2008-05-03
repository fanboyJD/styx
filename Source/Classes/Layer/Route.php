<?php
class Route {
	
	private static $mainLayer = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(&$get, &$post){
		self::$mainLayer = $get['n'][0];
		
		self::initializeLayer($get['n'][0], $get['n'][1], $get, $post);
	}
	
	public static function getMainLayer(){
		return self::$mainLayer;
	}
	
	public static function initializeLayer($layerName, $event, &$get, &$post){
		if(!$layerName || !Core::autoload($layerName, 'Layers'))
			return false;
		
		$layerName = strtolower($layerName);
		$class = ucfirst($layerName).'Layer';
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		$layer = new $class($layerName);
		$layer->handle($event, $get, $post);
	}
}
?>