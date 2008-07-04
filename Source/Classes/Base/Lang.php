<?php
class Lang extends StaticStorage {
	
	private static $lang = 'en';
	
	public static function setLanguage($lang){
		self::$lang = strtolower(pick($lang, 'en'));
		
		self::readFile();
	}
	
	private static function readFile(){
		$c = Cache::getInstance();
		
		if(!Core::retrieve('debug')){
			$array = $c->retrieve('Lang', self::$lang);
			if(is_array($array)){
				self::store($array);
				return;
			}
		}
		
		$file = realpath(Core::retrieve('app.path').'Language/'.self::$lang.'.lang');
		if(!$file || !file_exists($file))
			return;
		
		$regex = '/^|\s+([\w\.]+)\s*=\s*([\'\"])(.*?[^\\\]|)\2;/ism';
		
		$content = file_get_contents($file);
		
		preg_match_all('/namespace\s+([\w\.]+)\s*\{(.*)\}/ism', $content, $m);
		
		if(is_array($m[1]))
			foreach($m[1] as $key => $val){
				$content = str_replace($m[0][$key], '', $content);
				
				preg_match_all($regex, $m[2][$key], $vars);
				
				if(is_array($vars[1]))
					foreach($vars[1] as $k => $v)
						if($v && $vars[3][$k])
							$array[$val.'.'.$v] = str_replace("\'", "'", $vars[3][$k]);
			}
		
		preg_match_all($regex, $content, $m);
		
		if(is_array($m[1]))
			foreach($m[1] as $k => $v)
				if($v && $m[3][$k])
					$array[$v] = str_replace("\'", "'", $m[3][$k]);
		
		self::store($c->store('Lang', self::$lang, $array, ONE_DAY));
	}
	
	public static function get(){
		$args = func_get_args();
		
		$args[0] = self::retrieve($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
}