<?php
/*
 * Styx::String - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Provides useful String methods
 *
 */

class String {
	
	public static function ends($string, $look){
		return mb_strrpos($string, $look)===mb_strlen($string)-mb_strlen($look);
	}
	
	public static function starts($string, $look){
		return mb_strpos($string, $look)===0;
	}
	
	public static function length($string){
		return mb_strlen((string)$string);
	}
	
	public static function toUpper($string){
		return mb_strtoupper($string);
	}
	
	public static function toLower($string){
		return mb_strtolower($string);
	}
	
	public static function sub($string, $start, $length = null){
		if($length) return mb_substr($string, $start, $length);
		
		return mb_substr($string, $start);
	}
	
	public static function ucfirst($string){
		$a = mb_substr($string, 0, 1);
		$b = mb_substr($string, 1);
		
		return mb_strtoupper($a).mb_strtolower($b);
	}
	
	public static function replace($search, $replace, $subject, $count = null){
		return str_replace($search, $replace, $subject, $count);
	}
	
	public static function convert($array){
		if(!is_array($array))
			return iconv('UTF-8', 'UTF-8//IGNORE', $array);
		
		foreach(Hash::splat($array) as $k => $v)
			if(is_array($v)) $array[$k] = self::convert($v);
			else $array[$k] = iconv('UTF-8', 'UTF-8//IGNORE', $v);
		
		return $array;
	}
	
}