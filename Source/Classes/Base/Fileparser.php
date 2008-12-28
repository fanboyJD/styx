<?php
/*
 * Styx::Fileparser - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parse files to retrieve their values (e.g. for Language-Strings)
 *
 */

class Fileparser extends Storage {
	
	/* Big thanks to sorccu from the MooTools-Channel =) */
	private static $regex = array(
			'/\s*([\w\.]+)\s*=\s*([\'\"])(.*?[^\\\]|)\2;/ism',
			'/namespace\s+([\w\.]+)\s*\{((?:([\"\'])(?:.*?[^\\\\]|)\3|[^\}])*)\}/ism',
		),
		$replaces = array(
			array("\'", "\""),
			array("'", '"'),
		);
	
	public function __construct($file){
		$c = Cache::getInstance();
		
		$file = Core::retrieve('app.path').$file;
		$identifier = md5($file);
		if(!Core::retrieve('debug')){
			$array = $c->retrieve('Fileparser/'.$identifier);
			if(is_array($array)){
				$this->store($array);
				return;
			}
		}
		
		$file = realpath($file);
		if(!$file || !file_exists($file))
			return;
		
		$array = array();
		$content = file_get_contents($file);
		
		preg_match_all(self::$regex[1], $content, $m);
		if(!empty($m[1]))
			foreach($m[1] as $key => $val){
				$content = str_replace($m[0][$key], '', $content);
				
				Hash::extend($array, $this->parse($m[2][$key], $val));
			}
		
		$this->store($c->store('Fileparser/'.$identifier, Hash::extend($array, $this->parse($content)), ONE_WEEK));
	}
	
	private function parse($content, $prefix = null){
		preg_match_all(self::$regex[0], $content, $m);
		
		if(is_array($m[1]))
			foreach($m[1] as $k => $v)
				if($v && !empty($m[3][$k]))
					$array[($prefix ? $prefix.'.' : '').$v] = str_replace(self::$replaces[0], self::$replaces[1], $m[3][$k]);
		
		return pick($array, array());
	}
	
}