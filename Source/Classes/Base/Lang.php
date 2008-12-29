<?php
/*
 * Styx::Lang - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Reads the language file and provides an interface to retrieve the data
 *
 */

class Lang {
	
	/**
	 * @var Fileparser
	 */
	private static $Fileparser,
		$lang = 'en';
	
	public static function setLanguage($lang){
		self::$lang = String::toLower(pick($lang, 'en'));
		
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