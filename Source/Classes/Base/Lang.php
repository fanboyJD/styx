<?php
class Lang {
	
	/**
	 * @var Fileparser
	 */
	private static $Fileparser;
	private static $lang = 'en';
	
	public static function setLanguage($lang){
		self::$lang = strtolower(pick($lang, 'en'));
		
		self::$Fileparser = new Fileparser('Language/'.self::$lang.'.lang');
	}
	
	public static function getLanguage(){
		return self::$lang;
	}
	
	public static function retrieve($string){
		if(!self::$Fileparser) return;
		
		return self::$Fileparser->retrieve($string);
	}
	
	public static function get(){
		if(!self::$Fileparser) return;
		
		$args = func_get_args();
		
		$args[0] = self::$Fileparser->retrieve($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
}