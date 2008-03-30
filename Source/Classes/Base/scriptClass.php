<?php
class Script {
	private static $script = array(),
		$API = array('log', 'debug', 'info', 'warn', 'error', 'assert', 'dir', 'dirxml', 'trace', 'group', 'groupEnd', 'time', 'timeEnd', 'profile', 'profileEnd', 'count');
	
	public static function set($script, $add = false){
		self::$script[$add ? 'add' : 'ready'] .= $script;
	}
	
	public static function log($script, $type = 'log'){
		self::set('console.'.(in_array($type, self::$API) ? strtolower($type) : self::$API[0]).'('.json_encode($script).');');
	}
	
	public static function get(){
		return Template::map('Core', 'script')->assign(Util::cleanWhitespaces(self::$script, true))->parse(true);
	}
}
?>