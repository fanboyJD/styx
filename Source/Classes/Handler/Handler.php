<?php
class Handler {
	
	private static $Handler;
	
	public function initialize($handler){
		if(self::$Handler)
			return;
		
		$handler .= 'Handler';
		
		if(!Core::classFileExists($handler))
			$handler = 'HTMLHandler';
		
		self::$Handler = new $handler($this);
		
		return self::$Handler;
	}
	
	public function __call($name, $args){
		if(!self::$Handler || !method_exists(self::$Handler, $name))
			return false;
		
		return call_user_func_array(array(self::$Handler, $name), $args);
	}
	
	public static function setHeader($name, $value){
		header($name.': '.$value);
	}
}
?>