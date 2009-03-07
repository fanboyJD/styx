<?php

class Trace {
	
	protected static $version = '0.2.1';
	protected static $API = array('log', 'info', 'warn', 'error', 'trace');
	
	public static function errorHandler($errno, $errstr, $errfile, $errline){
		self::inspect($errstr.' in '.$errfile.' on line '.$errline);
	}
	
	public static function error($data, $return = false){
		if(!Core::retrieve('debug')) return $return;
		
		self::process($data, 'error');
		
		return $return;
	}
	
	public static function inspect($data = null, $return = false){
		if(!Core::retrieve('debug')) return $return;
		
		$trace = debug_backtrace();
		
		$array = array();
		
		foreach($trace as $t){
			array_shift($trace);
			
			if(!empty($t['class']) && in_array(strtolower($t['class']), array('trace', 'traceprototype')))
				continue;
			
			foreach(array('class', 'type', 'function', 'file', 'line', 'args') as $v)
				if(isset($t[$v]))
					$array[ucfirst($v)] = $t[$v];
			
			break;
		}
		
		static $traces = 0;
		self::log(Hash::extend($array, array(
			'Message' => $data ? $data : 'Trace #'.(++$traces),
			'Trace' => $trace,
		)), 'trace');
		
		return $return;
	}
	
	public static function log($data, $type = 'log'){
		if(!Core::retrieve('debug')) return;
		
		$type = strtolower($type);
		
		// PHP is great, $t will be available later on without further intializing :)
		foreach(debug_backtrace() as $t){
			if(!empty($t['class']) && in_array(strtolower($t['class']), array('trace', 'traceprototype')))
				continue;
			
			break;
		}
		
		$output = json_encode(array(array(
			'Type' => (in_array($type, self::$API) ? $type : self::$API[0]),
			'File' => !empty($t['file']) ? $t['file'] : __FILE__,
			'Line' => !empty($t['line']) ? $t['line'] : __LINE__,
		), $data));
		
		$headers = array();
   		
		static $index = 1;
		$parts = explode("\n", chunk_split($output, 5000, "\n"));
		for($i=0, $l = count($parts);$i<$l;$i++){
			if(empty($parts[$i])) continue;
			
			if($l>2) $headers['X-Wf-1-1-1-'.$index++] = ($i==0 ? strlen($output) : '').'|'.$parts[$i].'|'.($i<$l-2 ? '\\' : '');
			else $headers['X-Wf-1-1-1-'.$index++] = strlen($parts[$i]).'|'.$parts[$i].'|';
		}
		
	    $headers['X-Wf-1-Index'] = $index-1;
	    
	    self::setHeader();
	  	Response::setHeader($headers);
	}
	
	protected static function setHeader(){
		static $headers;
		
		if($headers++) return;
		
		Response::setHeader(array(
			'X-Wf-Protocol-1' => 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2',
			'X-Wf-1-Plugin-1' => 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/'.self::$version,
			'X-Wf-1-Structure-1' => 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1',
		));
	}
	
}