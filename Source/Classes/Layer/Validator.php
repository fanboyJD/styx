<?php
class Validator {
	
	private static $Instance;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function check($data){
		if(!$data || !is_array($data))
			return false;
		
		foreach($data as $k => $v)
			if(!ctype_digit((string)$k) && is_array($v) && !self::call($v[0], $v[1]))
				return false;
		
		return true;
	}
	
	public static function call($data, $options){
		if(!self::$Instance)
			self::$Instance = new Validator();
		
		splat($options);
		if(method_exists(self::$Instance, $options[0]))
			return self::$Instance->{$options[0]}($data, $options[1]);
		
		return true;
	}
	
	public function pagetitle($data){
		return Data::pagetitle($data)==$data;
	}
	
	public function mail($data){
		if(!$data)
			return false;
		
		foreach(array('@', '.') as $v){
			$pos = strpos($data, $v);
			if(!$pos || $pos+1==strlen($data))
				return false;
		}
		
		foreach(array('"', "'", '\\', '/') as $v)
			if(strpos($data, $v))
				return false;
		
		return true;
	}
	
	public function id($data){
		return Data::id($data)>0;
	}
	
	public function numericrange($data, $options){
		$data = Data::id($data);
		if(($data || ($data==0 && is_numeric($data))) && $data>=$options[0] && $data<=$options[1])
			return true;
		
		return false;
	}
	
	public function bool($data){
		return self::numericrange($data, array(0, 1));
	}
	
	public function date($data, $options = array()){
		$time = Data::date($data, $options);
		if(!$time || $time>time() || $time<-2051222961)
			return false;
		
		return true;
	}
	
}
?>