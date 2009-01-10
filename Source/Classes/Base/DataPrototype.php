<?php
/*
 * Styx::Data - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Format/Validate/Sanitize Input-data
 *
 */

class DataPrototype {
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($data, $validators){
		static $Instance, $Methods = array();
		
		if(!$Instance) $Instance = new Data();
		
		if(!Hash::length($Methods))
			foreach(get_class_methods('Data') as $method)
				array_push($Methods, String::toLower($method));
		
		if(is_string($validators))
			$validators = array($validators => true);
		
		foreach($validators as $validator => $options){
			if(empty($options) || !in_array(String::toLower($validator), $Methods))
				continue;
			
			$data = $Instance->{$validator}($data, is_array($options) ? $options : null, $validators);
		}
		
		return $data;
	}
	
	public static function add($string){
		return addslashes($string);
	}
	
	public static function strip($string){
		if(!$string) return '';
		
		if(is_array($string))
			return array_map('Data::strip', $string);
		
		return stripslashes($string);
	}
	
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
		
		return String::replace($replaces[0], $replaces[1], $string);
	}
	
	public static function sanitize($string){
		return self::escape(trim(htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false)));
	}
	
	public static function id($int, $divider = 0){
		if(!is_numeric($int) || $int<0) return 0;
		
		if($divider){
			$remainder = $int/$divider;
			if(round($remainder)!=$remainder)
				$int -= $int%$divider;
		}
		
		return round($int);
	}
	
	public static function bool($data){
		return $data=='false' ? false : !!$data;
	}
	
	public static function numericrange($data, $options){
		$data = self::id($data);
		return ($data>=$options[0] && $data<=$options[1] ? $data : 0);
	}
	
	public static function date($data, $options = array(
		'separator' => null,
		'order' => null,
		'future' => false,
	)){
		$data = explode(!empty($options['separator']) ? $options['separator'] : '.', $data);
		
		foreach(str_split(!empty($options['order']) ? $options['order'] : 'dmy') as $k => $v){
			if(!self::id($data[$k])) return null;
			
			$input[$v] = $data[$k];
		}
		
		if(!checkdate($input['m'], $input['d'], $input['y']))
			return false;
		
		return mktime(0, 0, 0, $input['m'], $input['d'], $input['y']);
	}
	
	public static function nullify($data){
		if(is_array($data))
			foreach($data as $k => &$val){
				$num = array(
					is_numeric($val) && $val==0,
					ctype_digit((string)$val),
				);
				
				if(!$val && !$num[0]) unset($data[$k]);
				elseif($num[0] || $num[1]) $val = self::id($val);
				elseif(is_array($val)) $val = self::nullify($val);
			}
		
		return Hash::splat($data);
	}
	
	public static function clean($array, $whitespaces = false){
		if(is_array($array)){
			foreach($array as $k => &$val){
				$val = self::clean($val, $whitespaces);
				
				if(!$val && $val!==0) unset($array[$k]);
			}
		}else{
			$array = trim($array);
			if($whitespaces) $array = String::replace(array("\r\n", "\t", "\n", "\r"), array($whitespaces=='clean' ? "\n" : " ", "", $whitespaces=='clean' ? "\n" : " ", ""), $array);
		}
		
		return $array;
	}
	
	public static function pagetitle($title, $options = array(
		'id' => null, // Key may be different
		'identifier' => null,
		'contents' => null,
	)){
		static $regex;
		
		if(!$regex){
			$regex = array(
				explode(' ', 'Æ æ Œ œ ß Ü ü Ö ö Ä ä À Á Â Ã Ä Å &#260; &#258; Ç &#262; &#268; &#270; &#272; Ð È É Ê Ë &#280; &#282; &#286; Ì Í Î Ï &#304; &#321; &#317; &#313; Ñ &#323; &#327; Ò Ó Ô Õ Ö Ø &#336; &#340; &#344; Š &#346; &#350; &#356; &#354; Ù Ú Û Ü &#366; &#368; Ý Ž &#377; &#379; à á â ã ä å &#261; &#259; ç &#263; &#269; &#271; &#273; è é ê ë &#281; &#283; &#287; ì í î ï &#305; &#322; &#318; &#314; ñ &#324; &#328; ð ò ó ô õ ö ø &#337; &#341; &#345; &#347; š &#351; &#357; &#355; ù ú û ü &#367; &#369; ý ÿ ž &#378; &#380;'),
				explode(' ', 'Ae ae Oe oe ss Ue ue Oe oe Ae ae A A A A A A A A C C C D D D E E E E E E G I I I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a a a a c c c d d e e e e e e g i i i i i l l l n n n o o o o o o o o r r s s s t t u u u u u u y y z z z'),
			);
			
			$regex[0][] = '"';
			$regex[0][] = "'";
		}
		
		$title = trim(String::sub(preg_replace('/([^A-z0-9]|_|\^)+/i', '_', String::replace($regex[0], $regex[1], $title)), 0, 64), '_');
		
		if(empty($options['identifier'])){
			static $identifier;
			
			if(!$identifier) $identifier = Core::retrieve('identifier');
			
			$options['identifier'] = $identifier;
		}
		
		return !empty($options['contents']) ? self::checkTitle($title, $options) : $title;
	}
	
	private static function checkTitle($title, $options = array(
		'id' => null, // Key may be different
		'identifier' => null,
		'contents' => null,
	), $i = 0){
		if(!is_array($options['contents'])) return $title;
		
		foreach($options['contents'] as $content){
			if(!is_array($content)) $content = array($options['identifier']['external'] => $content);
			
			if((empty($options[$options['identifier']['internal']]) || $options[$options['identifier']['internal']]!=$content[$options['identifier']['internal']]) && String::toLower($content[$options['identifier']['external']])==String::toLower($title.($i ? '_'.$i : '')))
				return self::checkTitle($title, $options, ++$i);
		}
		
		return $title.($i ? '_'.$i : '');
	}
	
	public static function purify($data, $options = array()){
		$purify = new Safehtml($options);
		
		return self::escape($purify->parse($data));
	}
	
	public static function excerpt($data, $options = array(
		'length' => 400,
		'purify' => true,
		'dots' => true,
		'options' => false,
	)){
		if(String::length($data)<$options['length']) return $data;
		
		$data = String::sub($data, 0, $options['length']);
		
		preg_match('/(\s+(?!([^<]+)?>)(?!.*\s+).*)/is', $data, $m);
		
		if(!empty($m[1])){
			$pos = strrpos($data, $m[1]);
			if($pos!==false) $data = String::sub($data, 0, $pos);
		}
		
		return (!empty($options['purify']) ? self::purify($data, $options['options']) : $data).(!empty($options['dots']) ? '...' : '');
	}
	
	public static function encode($data, $options = array(
		'whitespace' => true,
	)){
		return json_encode(self::clean($data, $options['whitespace']));
	}
	
}