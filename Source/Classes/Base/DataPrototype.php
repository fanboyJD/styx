<?php
/**
 * Styx::DataPrototype - Is only accessed via {@see Data}. Is used to sanitize data to prevent malicious input
 *
 * @package Styx
 * @subpackage Base
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class DataPrototype {
	
	protected function __construct(){}
	protected function __clone(){}
	
	/**
	 * Calls all methods listed in {@see $validators} on {@see $data} 
	 *
	 * @param mixed $data
	 * @param array $validators
	 * @return mixed
	 */
	public static function call($data, $validators){
		static $Instance, $Methods = array();
		
		if(!$Instance) $Instance = new Data();
		
		if(!Hash::length($Methods))
			$Methods = array_map('strtolower', get_class_methods($Instance));
		
		if(is_string($validators))
			$validators = array($validators => true);
		
		foreach($validators as $validator => $options){
			if(empty($options) || !in_array(strtolower($validator), $Methods))
				continue;
			
			$data = $Instance->{$validator}($data, is_array($options) ? $options : null, $validators);
		}
		
		return $data;
	}
	
	/**
	 * Escapes certain characters as listed in the configuration to prevent malicious input in templates
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escape($string){
		static $replaces;
		
		if($replaces===null){
			$encode = Core::retrieve('template.encode');
			
			$replaces = is_array($encode) ? array(
				array_keys($encode),
				array_values($encode),
			) : false;
		}
		
		if(!$replaces) return $string;
		
		return str_replace($replaces[0], $replaces[1], $string);
	}
	
	/**
	 * Escapes a string with {@see htmlspecialchars} and uses {@see Data::espace} to make the string safe for output
	 *
	 * @param string $string
	 * @return string
	 */
	public static function sanitize($string){
		return self::escape(trim(htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false)));
	}
	
	/**
	 * Always returns a positive integer or 0. If float is passed it rounds to the next integer value.
	 * If options is an int or an array with key 'divider' => int it rounds the integer to a number where
	 * the remainder is 0. For example Data::id(12, 10) returns 10
	 *
	 * @param mixed $int
	 * @param array|int $options
	 * @return int
	 */
	public static function id($int, $options = array()){
		if(!is_numeric($int) || $int<0) return 0;
		
		if($options){
			if(!is_array($options)) $options = array('divider' => $options);
			
			$remainder = $int/$options['divider'];
			if(round($remainder)!=$remainder)
				$int -= $int%$options['divider'];
		}
		
		return round($int);
	}
	
	/**
	 * Returns false on false values or a string that equals to "false" and true in any other case
	 *
	 * @param mixed $data
	 * @return bool
	 */
	public static function bool($data){
		return strtolower($data)==='false' ? false : !!$data;
	}
	
	/**
	 * Returns {@see $data} if the given value is within a given range
	 *
	 * @param int $data
	 * @param array $options
	 * @return int
	 */
	public static function numericrange($data, $options){
		$data = self::id($data);
		return $data>=$options[0] && $data<=$options[1] ? $data : 0;
	}
	
	/**
	 * Parses a date value consisting of day, month and year (for example TT.MM.YYYY) to a timestamp
	 *
	 * @param string $data
	 * @param array $options
	 * @return int
	 */
	public static function date($data, $options = array()){
		$default = array(
			'separator' => null,
			'order' => null,
			'future' => false,
		);
		
		Hash::extend($default, $options);
		
		$data = explode(pick($default['separator'], '.'), $data);
		
		foreach(str_split(pick($default['order'], 'dmy')) as $k => $v){
			if(!self::id($data[$k])) return null;
			
			$input[$v] = $data[$k];
		}
		
		if(!checkdate($input['m'], $input['d'], $input['y']))
			return null;
		
		$time = mktime(0, 0, 0, $input['m'], $input['d'], $input['y']);
		return !$default['future'] && $time>time() ? null : $time;
	}
	
	/**
	 * Sanitizes an url
	 *
	 * @param string $data
	 * @return string
	 */
	public static function url($data){
		if(!filter_var($data, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
			return null;
	
		if(!String::starts(strtolower($data), 'http://'))
			$data = 'http://'.$data;
		
		return strtolower($data)=='http://' ? null : self::sanitize($data);
	}
	
	/**
	 * Parses a string so it can safely be used in URLS
	 * For example "Die Äpfel" becomes "Die_Aepfel"
	 *
	 * @param string $data
	 * @param array $options
	 * @return string
	 */
	public static function pagetitle($data, $options = array()){
		static $regex;
		
		if(!$regex){
			$regex = array(
				explode(' ', 'Æ æ Œ œ ß Ü ü Ö ö Ä ä À Á Â Ã Ä Å &#260; &#258; Ç &#262; &#268; &#270; &#272; Ð È É Ê Ë &#280; &#282; &#286; Ì Í Î Ï &#304; &#321; &#317; &#313; Ñ &#323; &#327; Ò Ó Ô Õ Ö Ø &#336; &#340; &#344; Š &#346; &#350; &#356; &#354; Ù Ú Û Ü &#366; &#368; Ý Ž &#377; &#379; à á â ã ä å &#261; &#259; ç &#263; &#269; &#271; &#273; è é ê ë &#281; &#283; &#287; ì í î ï &#305; &#322; &#318; &#314; ñ &#324; &#328; ð ò ó ô õ ö ø &#337; &#341; &#345; &#347; š &#351; &#357; &#355; ù ú û ü &#367; &#369; ý ÿ ž &#378; &#380;'),
				explode(' ', 'Ae ae Oe oe ss Ue ue Oe oe Ae ae A A A A A A A A C C C D D D E E E E E E G I I I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a a a a c c c d d e e e e e e g i i i i i l l l n n n o o o o o o o o r r s s s t t u u u u u u y y z z z'),
			);
			
			$regex[0][] = '"';
			$regex[0][] = "'";
		}
		
		$default = array(
			'id' => null,
			'identifier' => null,
			'contents' => null,
		);
		
		Hash::extend($default, $options);
		
		$data = trim(String::sub(preg_replace('/([^A-z0-9]|_|\^)+/i', '_', String::replace($regex[0], $regex[1], $data)), 0, 64), '_');
		
		if(!$default['identifier']){
			static $identifier;
			
			if(!$identifier) $identifier = Core::retrieve('identifier');
			
			$default['identifier'] = $identifier;
		}
		
		return $default['contents'] ? self::checkTitle($data, $default) : $data;
	}
	
	/**
	 * Internal method to determine the pagetitle if it collides with with another one given in the options
	 *
	 * @param string $data
	 * @param array $options
	 * @param int $i
	 * @return string
	 */
	protected static function checkTitle($data, $options = array(), $i = 0){
		if(!is_array($options['contents'])) return $data;
		
		foreach($options['contents'] as &$content){
			if(!is_array($content)) $content = array($options['identifier']['external'] => $content);
			
			if(empty($content[$options['identifier']['external']]))
				continue;
			
			if((empty($options['id']) || $options['id']!=$content[$options['identifier']['internal']]) && strtolower($content[$options['identifier']['external']])==strtolower($data.($i ? '_'.$i : '')))
				return self::checkTitle($data, $options, ++$i);
		}
		
		return $data.($i ? '_'.$i : '');
	}
	
	/**
	 * Removes all unwanted tags/attributes/values from HTML-Input to prevent XSS-Injections and the input of
	 * any malicious data. Generates valid HTML as though it might not be valid XHTML as defined by the W3C
	 *
	 * @param string $data
	 * @param array $options
	 * @return string
	 */
	public static function purify($data, $options = array()){
		$purify = new Safehtml($options);
		
		return self::escape($purify->parse($data));
	}
	
	/**
	 * Outputs an excerpt of a text but does not break inside words but only on whitespace characters
	 *
	 * @param string $data
	 * @param array $options
	 * @return string
	 */
	public static function excerpt($data, $options = array()){
		$default = array(
			'length' => 400,
			'purify' => true,
			'dots' => true,
			'options' => false,
		);
		
		Hash::extend($default, $options);
		
		if(String::length($data)<$default['length']) return $data;
		
		$data = String::sub($data, 0, $default['length']);
		
		preg_match('/(\s+(?!([^<]+)?>)(?!.*\s+).*)/is', $data, $m);
		
		if(!empty($m[1])){
			$pos = String::rpos($data, $m[1]);
			if($pos!==false) $data = String::sub($data, 0, $pos);
		}
		
		return ($default['purify'] ? self::purify($data, $default['options']) : $data).($default['dots'] ? '...' : '');
	}
	
	/**
	 * Encodes a value with {@see json_encode}
	 *
	 * @param mixed $data
	 * @param array $options
	 * @return string
	 */
	public static function encode($data, $options = array()){
		$default = array(
			'whitespace' => 'clean',
		);
		
		Hash::extend($default, $options);
		
		return json_encode(String::clean($data, $default['whitespace']));
	}
	
}