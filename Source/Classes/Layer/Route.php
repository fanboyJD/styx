<?php
class Route {
	
	public static function initialize($parts, $numbered){
		
		self::initializeLayer($numbered[0], $numbered[1], $parts);
	}
	
	public static function initializeLayer($class, $event, $values){
		if(!$class || !Core::autoload($class, 'Layers'))
			return false;
		
		$class = ucfirst(strtolower($class)).'Layer';
		$event = 'on'.ucfirst(strtolower($event));
		
		if(!call_user_func($class.'::isLayer'))
			return false;
		
		echo $class.'->'.$event;
	}
}
?>