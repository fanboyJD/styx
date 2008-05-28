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
		
		$content = file($file);
		foreach($content as $v){
			$v = explode('=', $v, 2);
			
			$array[trim($v[0])] = substr(trim($v[1]), 1, -1);
		}
		
		self::store($c->store('Lang', self::$lang, $array, ONE_DAY));
	}
	
	public static function get(){
		$args = func_get_args();
		
		$args[0] = self::retrieve($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
}