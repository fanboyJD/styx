<?php
/*
 * Styx::Validator - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Validates input data to enable feedback to be send to the client
 *
 */

class ValidatorPrototype {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($data, $validators){
		static $Instance, $Methods = array();
		
		if(!$Instance) $Instance = new Validator();
		
		if(!Hash::length($Methods))
			foreach(get_class_methods($Instance) as $method)
				array_push($Methods, String::toLower($method));
		
		if(is_string($validators))
			$validators = array($validators => true);
		
		if(!empty($validators['notempty']) && !empty($validators['purify']))
			$validators['notempty'] = array(
				'purify' => $validators['purify'],
			);
		
		foreach($validators as $validator => $options){
			if(empty($options) || !in_array(String::toLower($validator), $Methods))
				continue;
			
			if(!$Instance->{$validator}($data, is_array($options) ? $options : null))
				return $validator;
		}
		
		return true;
	}
	
	public function pagetitle($data){
		return Data::pagetitle($data)==$data;
	}
	
	public function mail($data){
		if(!$data) return false;
		
		return !!filter_var($data, FILTER_VALIDATE_EMAIL);
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
		if(!$time || (empty($options['future']) && $time>time()) || $time<-2051222961)
			return false;
		
		return true;
	}
	
	public function notempty($data, $options = null){
		if(!empty($options['purify'])) $data = Data::purify($data, $options['purify']);
		
		return !!trim($data);
	}
	
	public function length($data, $options){
		$data = trim($data);
		
		if(empty($options[1])) return String::length($data)<=$options[0];
		
		return String::length($data)>=$options[0] && String::length($data)<=$options[1];
	}
	
}