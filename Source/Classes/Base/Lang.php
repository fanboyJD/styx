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
		
		$content = file_get_contents($file);
		preg_match_all('/^([\w\.]+)\s*=\s*([\'\"])(.*?[^\\\]|)\2;/ism', $content, $m);
		
		if(is_array($m[1]))
			foreach($m[1] as $k => $v)
				$array[$v] = str_replace("\'", "'", $m[3][$k]);
		
		self::store($c->store('Lang', self::$lang, $array, ONE_DAY));
	}
	
	public static function get(){
		$args = func_get_args();
		
		$args[0] = self::retrieve($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
}