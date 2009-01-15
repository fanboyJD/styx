<?php
/**
 * Styx::Fileparser - Parse files with a custom format to retrieve their values (e.g. for Language-Strings)
 *
 * @package Styx
 * @subpackage Base
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Fileparser extends Storage {
	
	/**
	 * The regex to be used to match strings and namespaces.
	 * Big thanks to sorccu from the MooTools-Channel =)
	 *
	 * @var array
	 */
	private static $regex = array(
			'/\s*([\w\.]+)\s*=\s*([\'\"])(.*?[^\\\]|)\2;/ism',
			'/namespace\s+([\w\.]+)\s*\{((?:([\"\'])(?:.*?[^\\\\]|)\3|[^\}])*)\}/ism',
		);
	/**
	 * Removes \ from quotation marks
	 *
	 * @var array
	 */
	private static $replaces = array(
			array("\'", "\""),
			array("'", '"'),
		);
	
	/**
	 * Parses a file and stores the values to be accessed via the Storage-API
	 *
	 * @see Storage
	 * @param string $file
	 */
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
				$content = String::replace($m[0][$key], '', $content);
				
				Hash::extend($array, $this->parse($m[2][$key], $val));
			}
		
		$this->store($c->store('Fileparser/'.$identifier, Hash::extend($array, $this->parse($content)), ONE_WEEK));
	}
	
	/**
	 * Parses the namespace or the rest of the file and returns key => value pairs
	 *
	 * @param string $content
	 * @param string $prefix
	 * @return array
	 */
	private function parse($content, $prefix = null){
		preg_match_all(self::$regex[0], $content, $m);
		
		$array = array();
		if(is_array($m[1]))
			foreach($m[1] as $k => $v)
				if($v && !empty($m[3][$k]))
					$array[($prefix ? $prefix.'.' : '').$v] = String::replace(self::$replaces[0], self::$replaces[1], $m[3][$k]);
		
		return pick($array, array());
	}
	
}