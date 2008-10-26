<?php
/*
 * Styx::Hash - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Provides useful methods for altering Arrays
 *
 */

class Hash {
	
	public static function length($array){
		return is_array($array) ? pick(count($array)) : null;
	}
	
	public static function remove(&$array, $value){
		$i = array_search($value, $array);
		if($i!==false) unset($array[$i]);
	}
	
	public static function flatten(&$array, $prefix = null){
		$imploded = array();
		if($prefix) $prefix .= '.';
		
		foreach($array as $key => $val)
			if(is_array($val)) $imploded = array_merge($imploded, self::flatten($val, $prefix.$key));
			else $imploded[$prefix.$key] = $val;
		
		return $array = $imploded;
	}
	
	public static function extend(&$src, $extended){
		if(!Hash::length($extended)) return $src;
		
		foreach($extended as $key => $val)
			$src[$key] = is_array($val) ? self::extend($src[$key], $val) : $val;
		
		return $src;
	}
	
	public static function splat(&$array){
		return $array = !is_array($array) ? (is_null($array) ? array() : array($array)) : $array;
	}
	
	public static function args($args){
		return count($args)==1 ? self::splat($args[0]) : $args;
	}
	
}