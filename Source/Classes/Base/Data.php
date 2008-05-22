<?php
/*
	Used for formatting/validating/sanitizing database input stuff
*/
class Data {
	
	private static $titleRegex = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($data, $options){
		splat($options);
		if(method_exists('Data', $options[0]))
			return call_user_func(array('Data', $options[0]), $data, $options[1]);
		
		return $data;
	}
	
	public static function add($string){
		return addslashes($string);
	}
	
	public static function strip($string){
		if(!$string)
			return '';
		
		if(is_array($string)){
			foreach($string as &$val)
				$val = self::strip($val);
			
			return $string;
		}
		
		return stripslashes($string);
	}
	
	public static function specialchars($string){
		return trim(htmlspecialchars($string));
	}
	
	public static function id($int, $divider = 0){
		if(!is_numeric($int) || $int<0)
			return 0;
		
		if($divider){
			$remainder = $int/$divider;
			if(round($remainder)!=$remainder)
				$int -= $int%$divider;
		}
		
		return round($int);
	}
	
	public static function bool($data){
		if($data=='true' || self::id($data))
			return true;
		
		return false;
	}
	
	public static function numericrange($data, $options){
		$data = self::id($data);
		return ($data>=$options[0] && $data<=$options[1] ? $data : 0);
	}
	
	public static function date($data, $options = array(
		'separator' => '.',
		'order' => 'dmy',
	)){
		$data = explode($options['separator'] ? $options['separator'] : '.', $data);
		
		foreach(str_split($options['order'] ? $options['order'] : 'dmy') as $k => $v){
			if(!self::id($data[$k]))
				return null;
			
			$input[$v] = $data[$k];
		}
		
		return mktime(0, 0, 0, $input['m'], $input['d'], $input['y']);
	}
	
	public static function nullify($data){
		if(is_array($data))
			foreach($data as &$val){
				$num = array(
					is_numeric($val) && $val==0,
					ctype_digit((string)$val),
				);
				
				if(!$val && !$num[0])
					unset($val);
				elseif($num[0] || $num[1])
					$val = self::id($val);
				elseif(is_array($val))
					$val = self::nullify($val);
			}
		
		return $data;
	}
	
	public static function in($key, $array){
		return $key.' IN ('.implode(',', $array).')';
	}
	
	public static function implode($array){
		return implode('', array_flatten($array));
	}
	
	public static function clean($array, $whitespaces = false){
		if(is_array($array)){
			foreach($array as &$val){
				$val = self::clean($val, $whitespaces);
				
				if(!$val && $val!==0) unset($val);
			}
		}else{
			$array = trim($array);
			if($whitespaces)
				$array = str_replace(array("\t", "\n", "\r"), array("", " ", ""), $array);
			
		}
		
		return $array;
	}
	
	public static function pagetitle($title, $options = array(
		'id' => null,
		'contents' => null,
	)){
		if(!self::$titleRegex)
			self::$titleRegex = array(
				explode(' ', 'Æ æ Œ œ ß Ü ü Ö ö Ä ä À Á Â Ã Ä Å &#260; &#258; Ç &#262; &#268; &#270; &#272; Ð È É Ê Ë &#280; &#282; &#286; Ì Í Î Ï &#304; &#321; &#317; &#313; Ñ &#323; &#327; Ò Ó Ô Õ Ö Ø &#336; &#340; &#344; Š &#346; &#350; &#356; &#354; Ù Ú Û Ü &#366; &#368; Ý Ž &#377; &#379; à á â ã ä å &#261; &#259; ç &#263; &#269; &#271; &#273; è é ê ë &#281; &#283; &#287; ì í î ï &#305; &#322; &#318; &#314; ñ &#324; &#328; ð ò ó ô õ ö ø &#337; &#341; &#345; &#347; š &#351; &#357; &#355; ù ú û ü &#367; &#369; ý ÿ ž &#378; &#380;'),
				explode(' ', 'Ae ae Oe oe ss Ue ue Oe oe Ae ae A A A A A A A A C C C D D D E E E E E E G I I I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a a a a c c c d d e e e e e e g i i i i i l l l n n n o o o o o o o o r r s s s t t u u u u u u y y z z z'),
			);
		
		$title = trim(substr(preg_replace('/\_{2,}/i', '_', preg_replace('/[^A-Za-z0-9_]/i', '_', str_replace(self::$titleRegex[0], self::$titleRegex[1], $title))), 0, 64));
		if(self::id($title))
			$title = '_'.$title;
		
		if($options['contents'])
			$title = self::checkTitle($title, 0, $options);
		
		return $title;
	}
	
	private static function checkTitle($title, $i, $options = array(
		'contents' => null,
		'id' => null,
	)){
		if(!is_array($options['contents']))
			return $title;
		
		foreach($options['contents'] as $content)
			if((!$options['id'] || $options['id']!=$content['id']) && strtolower($content['pagetitle'])==strtolower($title.(self::id($i) ? (!endsWith($title, '_') ? '_' : '').$i : '')))
				return self::checkTitle($title, ++$i, $options);
		
		return $title.(self::id($i) ? (!endsWith($title, '_') ? '_' : '').$i : '');
	}
	
}
?>