<?php
class Script {
	public static $script = array(
		'domReady' => '',
		'add' => ''
	);
	private static $api = array('log', 'error', 'info', 'group', 'groupEnd');
	
	public static function set($script, $add = false){
		self::$script[$add ? 'add' : 'domReady'] .= $script;
	}
	public static function get(){
		self::$script['domReady'] = '<script type="text/javascript">window.addEvent("domready", function(){'.self::$script['domReady'].'});';
		self::$script['add'] .= '</script>';
		return implode(Util::cleanWhitespaces(self::$script, true));
	}
	public static function log($script, $type = 'log'){
		self::set('console.'.(in_array($type, self::$api) ? $type : 'log').'('.json_encode($script).');');
	}
}
?>