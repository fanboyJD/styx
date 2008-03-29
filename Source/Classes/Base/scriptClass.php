<?php
class Script {
	public static $script = array();
	private static $API = array('log', 'error', 'info', 'group', 'groupEnd');
	
	public static function set($script, $add = false){
		self::$script[$add ? 'add' : 'domReady'] .= $script;
	}
	
	public static function get(){
		return Template::getInstance('script')->assign(Util::cleanWhitespaces(self::$script, true))->parse(true);
	}
	
	public static function log($script, $type = 'log'){
		self::set('console.'.(in_array($type, self::$API) ? strtolower($type) : 'log').'('.json_encode($script).');');
	}
}
?>