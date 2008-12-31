<?php
/**
 * Styx::Script - Holds certain JavaScript to be streamed to the client
 * 
 * @package Styx
 * @subpackage Utility
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Script {
	private static $script = array('add' => '', 'ready' => ''),
		$API = array('log', 'debug', 'info', 'warn', 'error', 'assert', 'dir', 'dirxml', 'trace', 'group', 'groupEnd', 'time', 'timeEnd', 'profile', 'profileEnd', 'count');
	
	private function __construct(){}
	private function __clone(){}
	
	/**
	 * Stores the given JavaScript
	 *
	 * @param string $data
	 * @param bool $add
	 */
	public static function set($data, $add = false){
		self::$script[$add ? 'add' : 'ready'] .= $data;
	}
	
	/**
	 * Adds a "console.log" function call to be used with Debuggers like Firebug
	 *
	 * @param mixed $data Can be anything, even arrays
	 * @param string $type The method of the console object to be called (eg. debug, warn, info), defaults to "log"
	 */
	public static function log($data, $type = null){
		$type = String::toLower($type);
		
		self::set('console.'.(in_array($type, self::$API) ? $type : self::$API[0]).'('.json_encode($data).');');
	}
	
	/**
	 * Retrieves the JavaScript that was set via Script::set and Script::log
	 * Needs to be called right before the Page gets shown. See Application::onPageShow
	 * in Classes-Folder of the Sample Application for example usage.
	 * 
	 * @see Page::show()
	 * @see Core::fireEvent()
	 * @return string
	 */
	public static function get(){
		return Template::map('Utility', 'Script')->assign(Data::clean(self::$script, true))->parse(true);
	}
}