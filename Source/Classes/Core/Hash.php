<?php
/**
 * Styx::Hash - Provides useful methods to alter Arrays. Most methods that perform
 * operations on arrays also modify by reference
 *
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

final class Hash {
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * Returns the length of an array or null if no array was passed
	 *
	 * @param mixed $array
	 * @return int
	 */
	public static function length($array){
		return is_array($array) ? pick(count($array)) : null;
	}
	
	/**
	 * Removes all occurences of {@link $value} in {@link $array}
	 *
	 * @param array $array
	 * @param mixed $value
	 * @return array
	 */
	public static function remove(&$array, $value){
		if(!in_array($value, $array)) return $array;
		
		return $array = array_diff_key($array, array_flip(array_keys($array, $value, true)));
	}
	
	/**
	 * Flattens a multi-dimensional array to a single-dimensional array. It uses
	 * the array keys to generate a flat array. array('key' => array('my' => 'value'))
	 * becomes array('key.my' => 'value')
	 *
	 * @param array $array
	 * @param string $prefix An optional prefix
	 * @return array
	 */
	public static function flatten(&$array, $prefix = null){
		if(!is_array($array)) return $array;
		
		$imploded = array();
		if($prefix) $prefix .= '.';
		
		foreach($array as $key => $val)
			if(is_array($val)) $imploded = array_merge($imploded, self::flatten($val, $prefix.$key));
			else $imploded[$prefix.$key] = $val;
		
		return $array = $imploded;
	}
	
	/**
	 * Copies all the properties from the second array to the first array. Overwrites
	 * existing values in the first array
	 *
	 * @param array $src
	 * @param array $extended
	 * @return array
	 */
	public static function extend(&$src, $extended){
		if(!Hash::length($extended)) return $src;
		
		foreach($extended as $key => $val)
			$src[$key] = is_array($val) ? self::extend($src[$key], $val) : $val;
		
		return $src;
	}
	
	/**
	 * Returns the array if the passed in variable is an array or a new array with the 
	 * passed in value as the first element
	 *
	 * @param mixed $array
	 * @return array
	 */
	public static function splat(&$array){
		if(is_array($array)) return $array;
		
		return $array = (empty($array) ? array() : array($array));
	}
	
	/**
	 * Returns either the input array or its first element if it
	 * only contains one element
	 *
	 * @param mixed $args
	 * @return array
	 */
	public static function args($args){
		$args = self::splat($args);
		
		return count($args)==1 ? self::splat($args[0]) : $args;
	}
	
}