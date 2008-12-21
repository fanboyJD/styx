<?php
/*
 * Styx::Script - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Holds certain JavaScript to be streamed to the client
 *
 */

class Script {
	private static $script = array('add' => '', 'ready' => ''),
		$API = array('log', 'debug', 'info', 'warn', 'error', 'assert', 'dir', 'dirxml', 'trace', 'group', 'groupEnd', 'time', 'timeEnd', 'profile', 'profileEnd', 'count');
	
	private function __construct(){}
	private function __clone(){}
	
	public static function set($data, $add = false){
		self::$script[$add ? 'add' : 'ready'] .= $data;
	}
	
	public static function log($data, $type = null){
		$type = strtolower($type);
		
		self::set('console.'.(in_array($type, self::$API) ? $type : self::$API[0]).'('.json_encode($data).');');
	}
	
	public static function get(){
		return Template::map('Utility', 'Script')->assign(Data::clean(self::$script, true))->parse(true);
	}
}