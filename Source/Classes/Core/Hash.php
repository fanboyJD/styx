<?php
/*
 * Styx::Hash - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Provides useful methods for altering Arrays
 *
 */

final class Hash {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function length($array){
		return is_array($array) ? pick(count($array)) : null;
	}
	
	public static function remove(&$array, $value){
		if(!in_array($value, $array)) return $array;
		
		return $array = array_diff_key($array, array_flip(array_keys($array, $value, true)));
	}
	
	public static function flatten(&$array, $prefix = null){
		if(!is_array($array)) return $array;
		
		$imploded = array();
		if($prefix) $prefix .= '.';
		
		foreach($array as $key => $val)
			if(is_array($val)) $imploded = array_merge($imploded, self::flatten($val, $prefix.$key));
			else $imploded[$prefix.$key] = $val;
		
		return $array = $imploded;
	}
	
	public static function nullify($data){
		if(is_array($data))
			foreach($data as $k => &$val){
				$num = array(
					is_numeric($val) && $val==0,
					ctype_digit((string)$val),
				);
				
				if(!$val && !$num[0]) unset($data[$k]);
				elseif($num[0] || $num[1]) $val = Data::id($val);
				elseif(is_array($val)) $val = self::nullify($val);
			}
		
		return self::splat($data);
	}
	
	public static function extend(&$src, $extended){
		if(!Hash::length($extended)) return $src;
		
		foreach($extended as $key => $val)
			$src[$key] = is_array($val) ? self::extend($src[$key], $val) : $val;
		
		return $src;
	}
	
	public static function splat(&$array){
		if(is_array($array)) return $array;
		
		return $array = (empty($array) ? array() : array($array));
	}
	
	public static function args($args){
		$args = self::splat($args);
		
		return count($args)==1 ? self::splat($args[0]) : $args;
	}
	
}