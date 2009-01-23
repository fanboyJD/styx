<?php
/**
 * Styx::ValidatorPrototype - Is only accessed via {@see Validator}. Validates data and only returns true or false values in all methods,
 *
 * @package Styx
 * @subpackage Layer
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class ValidatorPrototype {
	
	protected function __construct(){}
	protected function __clone(){}
	
	/**
	 * Calls all methods listed in {@see $validators} on {@see $data} and returns true on success
	 * or false if one of the given validators fails
	 *
	 * @param mixed $data
	 * @param array $validators
	 * @return bool
	 */
	public static function call($data, $validators){
		static $Instance, $Methods = array();
		
		if(!$Instance) $Instance = new Validator();
		
		if(!Hash::length($Methods))
			$Methods = array_map('strtolower', get_class_methods($Instance));
		
		if(is_string($validators))
			$validators = array($validators => true);
		
		foreach($validators as $validator => $options){
			if(empty($options) || !in_array(strtolower($validator), $Methods))
				continue;
			
			if(!$Instance->{$validator}($data, is_array($options) ? $options : null, $validators))
				return $validator;
		}
		
		return true;
	}
	
	/**
	 * Validates to true if a call to {@see Data::id} with {@see $data} returns anything else than 0 (eg. a positive number)
	 *
	 * @param mixed $int
	 * @param array|int $options
	 * @return bool
	 */
	public function id($data, $options = array()){
		return !!Data::id($data, $options);
	}
	
	/**
	 * Returns true if the given value is not empty and is a valid E-Mail-Address
	 *
	 * @param string $data
	 * @return bool
	 */
	public function mail($data){
		$data = trim($data);
		
		if(!$data) return false;
		
		return !!filter_var($data, FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * Returns true if {@see $data} is within a given range
	 *
	 * @param int $data
	 * @param array $options
	 * @return bool
	 */
	public function numericrange($data, $options){
		$data = Data::id($data);
		return $data>=$options[0] && $data<=$options[1];
	}
	
	/**
	 * Parses a date value consisting of day, month and year (for example TT.MM.YYYY) to a timestamp
	 * and returns true if the input data is valid
	 *
	 * @param string $data
	 * @param array $options
	 * @return bool
	 */
	public function date($data, $options = array()){
		return !!Data::date($data, $options);
	}
	
	/**
	 * Returns if the given string is not empty, or if it is an url and it does not consist of "http://"
	 * If the validator "purify" is specified it first cleans the input of malicious code
	 *
	 * @param string $data
	 * @param array $options
	 * @param array $validators
	 * @return bool
	 */
	public function notempty($data, $options = null, $validators){
		if(!empty($validators['url']) && strtolower($data)=='http://')
			return false;
		
		if(!empty($validators['purify']))
			$data = Data::purify($data, $validators['purify']);
		
		return !!trim($data);
	}
	
	/**
	 * Returns true if the given string is within the given length
	 *
	 * @param string $data
	 * @param array $options
	 * @return bool
	 */
	public function length($data, $options){
		$data = trim($data);
		
		if(empty($options[1])) return String::length($data)<=$options[0];
		
		return String::length($data)>=$options[0] && String::length($data)<=$options[1];
	}
	
}