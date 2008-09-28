<?php
/*
 * Styx::Fileparser - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parse files to retrieve their values (e.g. for Language-Strings)
 *
 */

class Fileparser extends DynamicStorage {
	
	/* Big thanks to sorccu from the MooTools-Channel =) */
	private $regex = array(
			'/\s*([\w\.]+)\s*=\s*([\'\"])(.*?[^\\\]|)\2;/ism',
			'/namespace\s+([\w\.]+)\s*\{((?:([\"\'])(?:.*?[^\\\\]|)\3|[^\}])*)\}/ism',
		),
		$replaces = array(
			array("\'", "\""),
			array("'", '"'),
		);
	
	public function __construct($file){
		$c = Cache::getInstance();
		
		$identifier = md5($file);
		if(!Core::retrieve('debug')){
			$array = $c->retrieve('Fileparser', $identifier);
			if(is_array($array)){
				$this->store($array);
				return;
			}
		}
		
		$file = realpath(Core::retrieve('app.path').$file);
		if(!$file || !file_exists($file))
			return;
		
		$array = array();
		$content = file_get_contents($file);
		
		preg_match_all($this->regex[1], $content, $m);
		if(is_array($m[1]))
			foreach($m[1] as $key => $val){
				$content = str_replace($m[0][$key], '', $content);
				
				Hash::extend($array, $this->parse($m[2][$key], $val));
			}
		
		$this->store($c->store('Fileparser', $identifier, Hash::extend($array, $this->parse($content)), ONE_DAY));
	}
	
	private function parse($content, $prefix = null){
		preg_match_all($this->regex[0], $content, $m);
		
		if(is_array($m[1]))
			foreach($m[1] as $k => $v)
				if($v && $m[3][$k])
					$array[($prefix ? $prefix.'.' : '').$v] = str_replace($this->replaces[0], $this->replaces[1], $m[3][$k]);
		
		return pick($array, array());
	}
	
}