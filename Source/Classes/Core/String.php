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
		return strrpos($string, $look)===strlen($string)-strlen($look);
	}
	
	public static function starts($string, $look){
		return strpos($string, $look)===0;
	}
	
}