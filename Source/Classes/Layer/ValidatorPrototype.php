<?php
/*
 * Styx::Validator - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Validates input data to enable feedback to be send to the client
 *
 */

class ValidatorPrototype {
	
	protected function __construct(){}
	protected function __clone(){}
	
	public static function call($data, $validators){
		static $Instance, $Methods = array();
		
		if(!$Instance) $Instance = new Validator();
		
		if(!Hash::length($Methods))
			foreach(get_class_methods($Instance) as $method)
				array_push($Methods, String::toLower($method));
		
		if(is_string($validators))
			$validators = array($validators => true);
		
		foreach($validators as $validator => $options){
			if(empty($options) || !in_array(String::toLower($validator), $Methods))
				continue;
			
			if(!$Instance->{$validator}($data, is_array($options) ? $options : null, $validators))
				return $validator;
		}
		
		return true;
	}
	
	public function id($data){
		return !!Data::id($data);
	}
	
	public function pagetitle($data){
		return Data::pagetitle($data)==$data;
	}
	
	public function mail($data){
		$data = trim($data);
		
		if(!$data) return false;
		
		return !!filter_var($data, FILTER_VALIDATE_EMAIL);
	}
	
	public function numericrange($data, $options){
		$data = Data::id($data);
		if(($data || ($data==0 && is_numeric($data))) && $data>=$options[0] && $data<=$options[1])
			return true;
		
		return false;
	}
	
	public function date($data, $options = array()){
		$default = array(
			'separator' => null,
			'order' => null,
			'future' => false,
		);
		
		Hash::extend($default, $options);
		
		return !!Data::date($data, $default);
	}
	
	public function notempty($data, $options = null, $validators){
		$default = array(
			'purify' => null,
		);
		
		Hash::extend($default, $options);
		
		if(!empty($validators['url']) && String::toLower($data)=='http://')
			return false;
		
		if(!$default['purify'] && !empty($validators['purify']))
			$default['purify'] = $validators['purify'];
		
		if($default['purify']) $data = Data::purify($data, $default['purify']);
		
		return !!trim($data);
	}
	
	public function length($data, $options){
		$data = trim($data);
		
		if(empty($options[1])) return String::length($data)<=$options[0];
		
		return String::length($data)>=$options[0] && String::length($data)<=$options[1];
	}
	
}