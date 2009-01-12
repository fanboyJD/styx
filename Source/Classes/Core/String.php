<?php
/*
 * Styx::String - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Provides useful String methods
 *
 */

final class String {
	
	public static $Fn = array();
	public static $Features = array(
		'mbstring' => true,
		'iconv' => true,
	);
	
	private function __construct(){}
	private function __clone(){}
	
	public static function initialize($features){
		foreach(self::$Features as $k => $v)
			self::$Features[$k] = empty($features[$k]) ? false : !!$features[$k];
		
		foreach(array(
			'strlen', 'strrpos', 'strpos', 'strlen', 'strtoupper', 'strtolower',
			'substr', 'substr_count', 'stripos', 'strripos',
		) as $v)
			self::$Fn[$v] = (self::$Features['mbstring'] ? 'mb_' : '').$v;
		
		if(self::$Features['mbstring']) mb_internal_encoding('UTF-8');
	}
	
	public static function ends($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['strrpos']($string, $look)===$Fn['strlen']($string)-$Fn['strlen']($look);
	}
	
	public static function starts($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['strpos']($string, $look)===0;
	}
	
	public static function length($string){
		$Fn = self::$Fn;
		
		return $Fn['strlen']((string)$string);
	}
	
	public static function toUpper($string){
		$Fn = self::$Fn;
		
		return $Fn['strtoupper']($string);
	}
	
	public static function toLower($string){
		$Fn = self::$Fn;
		
		return $Fn['strtolower']($string);
	}

	public static function pos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strpos']($string, $look, pick($offset, 0));
	}
	
	public static function ipos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['stripos']($string, $look, pick($offset, 0));
	}
	
	public static function rpos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strrpos']($string, $look, pick($offset, 0));
	}
	
	public static function ripos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strripos']($string, $look, pick($offset, 0));
	}
	
	public static function sub($string, $start, $length = null){
		$Fn = self::$Fn;
		
		if($length) return $Fn['substr']($string, $start, $length);
		return $Fn['substr']($string, $start);
	}
	
	public static function subcount($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['substr_count']($string, $look);
	}
	
	public static function ucfirst($string){
		$Fn = self::$Fn;
		
		return $Fn['strtoupper']($Fn['substr']($string, 0, 1)).$Fn['strtolower']($Fn['substr']($string, 1));
	}
	
	public static function replace($search, $replace, $subject, $count = null){
		return str_replace($search, $replace, $subject, $count);
	}
	
	public static function clean($string, $whitespaces = false){
		if(is_array($string)){
			foreach($string as $k => &$val){
				$val = self::clean($val, $whitespaces);
				
				if(!$val && $val!==0) unset($string[$k]);
			}
		}else{
			$string = trim($string);
			if($whitespaces) $string = self::replace(array("\r\n", "\t", "\n", "\r"), array($whitespaces=='clean' ? "\n" : " ", "", $whitespaces=='clean' ? "\n" : " ", ""), $string);
		}
		
		return $string;
	}
	
	public static function convert($array){
		if(!self::$Features['iconv']) return $array;
		
		if(!is_array($array))
			return iconv('UTF-8', 'UTF-8//IGNORE', $array);
		
		array_walk_recursive($array, 'self::convert');
		
		return $array;
	}
	
}