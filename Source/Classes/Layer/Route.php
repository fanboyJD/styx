<?php
class Route {
	
	private static $mainLayer = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize(&$get, &$post){
		self::$mainLayer = $get['n'][0];
		
		Layer::run($get['n'][0], $get['n'][1], $get, $post);
	}
	
	public static function getMainLayer(){
		return self::$mainLayer;
	}
}