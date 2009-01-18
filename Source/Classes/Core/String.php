<?php
/**
 * Styx::String - Acts as a wrapper to String methods. Either uses the mbstring extension
 * or falls back to the native php functions.
 *
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

final class String {
	
	/**
	 * Contains a list of the used string functions (either native or prefixed with mb_)
	 *
	 * @var array
	 */
	public static $Fn = array();
	/**
	 * Holds information about the currently installed php extensions related
	 * to this Class
	 *
	 * @var array
	 */
	public static $Features = array(
		'mbstring' => true,
		'iconv' => true,
	);
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * Initializes the String class and decides whether to use mbstring or native php string functions
	 * based on the feature settings passed in
	 *
	 * @param array $features
	 */
	public static function initialize($features){
		foreach(self::$Features as $k => $v)
			self::$Features[$k] = empty($features['feature.'.$k]) ? false : !!$features['feature.'.$k];
		
		foreach(array(
			'strlen', 'strrpos', 'strpos', 'strlen', 'strtoupper', 'strtolower',
			'substr', 'substr_count', 'stripos', 'strripos',
		) as $v)
			self::$Fn[$v] = (self::$Features['mbstring'] ? 'mb_' : '').$v;
		
		if(self::$Features['mbstring']) mb_internal_encoding('UTF-8');
	}
	
	/**
	 * Checks if the string ends with a certain value
	 *
	 * @param string $string
	 * @param string $look
	 * @return bool
	 */
	public static function ends($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['strrpos']($string, $look)===$Fn['strlen']($string)-$Fn['strlen']($look);
	}
	
	/**
	 * Checks if the string starts with a certain value
	 *
	 * @param string $string
	 * @param string $look
	 * @return bool
	 */
	public static function starts($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['strpos']($string, $look)===0;
	}
	
	/**
	 * Wrapper for strlen
	 *
	 * @see strlen
	 * @param string $string
	 * @return int
	 */
	public static function length($string){
		$Fn = self::$Fn;
		
		return $Fn['strlen']((string)$string);
	}
	
	/**
	 * Wrapper for strtoupper
	 *
	 * @see strtoupper
	 * @param string $string
	 * @return string
	 */
	public static function toUpper($string){
		$Fn = self::$Fn;
		
		return $Fn['strtoupper']($string);
	}
	
	/**
	 * Wrapper for strtolower
	 *
	 * @see strtolower
	 * @param string $string
	 * @return string
	 */
	public static function toLower($string){
		$Fn = self::$Fn;
		
		return $Fn['strtolower']($string);
	}

	/**
	 * Wrapper for strpos
	 *
	 * @see strpos
	 * @param string $string
	 * @param string $look
	 * @param int $offset
	 * @return bool|int
	 */
	public static function pos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strpos']($string, $look, pick($offset, 0));
	}
	
	/**
	 * Wrapper for stripos
	 *
	 * @see stripos
	 * @param string $string
	 * @param string $look
	 * @param int $offset
	 * @return bool|int
	 */
	public static function ipos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['stripos']($string, $look, pick($offset, 0));
	}
	
	/**
	 * Wrapper for strrpos
	 *
	 * @see strrpos
	 * @param string $string
	 * @param string $look
	 * @param int $offset
	 * @return bool|int
	 */
	public static function rpos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strrpos']($string, $look, pick($offset, 0));
	}
	
	/**
	 * Wrapper for strripos
	 *
	 * @see strripos
	 * @param string $string
	 * @param string $look
	 * @param int $offset
	 * @return bool|int
	 */
	public static function ripos($string, $look, $offset = null){
		$Fn = self::$Fn;
		
		return $Fn['strripos']($string, $look, pick($offset, 0));
	}
	
	/**
	 * Wrapper for substr
	 *
	 * @see substr
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @return string
	 */
	public static function sub($string, $start, $length = null){
		$Fn = self::$Fn;
		
		if($length) return $Fn['substr']($string, $start, $length);
		return $Fn['substr']($string, $start);
	}
	
	/**
	 * Wrapper for substr_count
	 *
	 * @see substr_count
	 * @param string $string
	 * @param string $look
	 * @return int
	 */
	public static function subcount($string, $look){
		$Fn = self::$Fn;
		
		return $Fn['substr_count']($string, $look);
	}
	
	/**
	 * Wrapper for ucfirst
	 *
	 * @see ucfirst
	 * @param string $string
	 * @return string
	 */
	public static function ucfirst($string){
		$Fn = self::$Fn;
		
		return $Fn['strtoupper']($Fn['substr']($string, 0, 1)).$Fn['strtolower']($Fn['substr']($string, 1));
	}
	
	/**
	 * Wrapper for str_replace
	 *
	 * @see str_replace
	 * @param mixed $search
	 * @param mixed $replace
	 * @param string $string
	 * @param int $count
	 * @return string
	 */
	public static function replace($search, $replace, $subject, $count = null){
		return str_replace($search, $replace, $subject, $count);
	}
	
	/**
	 * Removes unnecessary whitespaces. If an array is passed it recursively cleans it,
	 * typecasts floats and integers accordingly and unsets empty values
	 *
	 * @param mixed $string
	 * @param bool|string $whitespaces
	 * @return mixed
	 */
	public static function clean($string, $whitespaces = true){
		if(is_array($string)){
			foreach($string as $k => &$v){
				if($v==(string)(float)$v) $v = (float)$v;
				elseif($v==='0' || $v===0 || ctype_digit((string)$v)) $v = Data::id($v);
				elseif(is_array($v)) $v = self::clean($v, $whitespaces);
				elseif(!$v || !trim($v)) unset($string[$k]);
				else $v = self::clean($v, $whitespaces);
			}
		}else{
			$string = trim($string);
			if($whitespaces) $string = self::replace(array("\r\n", "\n", "\t"), array(($whitespaces==='clean' ? " " : "\n"), ($whitespaces==='clean' ? " " : "\n"), ""), $string);
		}
		
		return $string;
	}
	
	/**
	 * Removes all invalid UTF-8 bytes when iconv is available
	 *
	 * @param mixed $array
	 * @return mixed
	 */
	public static function convert($array){
		if(!self::$Features['iconv']) return $array;
		
		$ini = error_reporting(0);
		
		if(is_array($array))
			array_walk_recursive($array, array('self', 'convert'));
		else 
			$array = iconv('UTF-8', 'UTF-8//IGNORE', $array);
		
		error_reporting($ini);
		
		return $array;
	}
	
}