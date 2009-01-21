<?php
/**
 * Styx::Lang - Reads the file corresponding to the current language and provides an interface to retrieve the data
 *
 * @package Styx
 * @subpackage Base
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

final class Lang {
	
	/**
	 * Holds the Fileparser-Instance
	 *
	 * @var Fileparser
	 */
	private static $Fileparser;
	/**
	 * The currently used language
	 *
	 * @var string
	 */
	private static $lang = 'en';
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * Changes the language to the passed language and (tries) to parse the language-file's contents
	 *
	 * @param string $lang
	 */
	public static function setLanguage($lang){
		self::$lang = strtolower(pick($lang, 'en'));
		
		self::$Fileparser = new Fileparser('Language/'.self::$lang.'.lang');
	}
	
	/**
	 * Returns the current language
	 *
	 * @return string
	 */
	public static function getLanguage(){
		return self::$lang;
	}
	
	/**
	 * Returns a string by its key
	 *
	 * @param string $key
	 * @return string
	 */
	public static function retrieve($key){
		if(!self::$Fileparser) return;
		
		return self::$Fileparser->retrieve($key);
	}
	
	/**
	 * Returns a string by its key and calls sprintf on it
	 *
	 * @param string $key
	 * @param mixed $args,...
	 * @return string
	 */
	public static function get(){
		if(!self::$Fileparser) return;
		
		$args = func_get_args();
		
		$args[0] = self::$Fileparser->retrieve($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
}