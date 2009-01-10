<?php
/*
 * Styx::Page - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses all data to stream it to the client
 *
 */

class Page extends Template {
	
	private $Templates = array(),
		$substitution = null;
	
	protected function __construct(){
		$this->base = 'Page';
		
		$time = time();
		Response::setHeader(array(
			'Expires' => date('r', $time-1000),
			'Last-Modified' => date('r', $time),
			'Cache-Control' => 'no-cache, no-store, must-revalidate',
		));
		
		$this->bind($this);
	}
	
	/**
	 * @return Page
	 */
	public static function map(){
		return self::getInstance();
	}
	
	/**
	 * 
	 * Using getInstance is more intuitive than map
	 * 
	 * @return Page
	 */
	public static function getInstance(){
		static $Instance;
		
		return $Instance ? $Instance : $Instance = new Page();
	}
	
	public function register($name, $obj){
		if(in_array($obj, $this->Templates))
			$this->deregister($obj);
		
		$this->Templates[$name] = $obj;
	}
	
	public function deregister($obj){
		Hash::remove($this->Templates, $obj);
	}
	
	/**
	 * Useful for JSON: This Method sets a key for later substitution. Only the
	 * assigned variable with the given key will be send to output and it will replace
	 * the original assignment array. 
	 * 
	 * @return Page
	 */
	public function substitute($key){
		$this->substitution = $key;
		
		return $this;
	}
	
	public function show($return = false){
		$ContentType = Response::retrieveContentType();
		
		$assign = array();
		
		if(Hash::length($this->Templates))
			foreach($this->Templates as $k => $v)
				$assign[$k] = $v->parse(true);
		
		$main = Route::getMainlayer();
		if($main && !empty($assign['layer.'.$main]))
			$assign['layer'] = $assign['layer.'.$main];
		
		$this->assign($assign);
		
		if($ContentType->getType()=='html')
			PackageManager::assignPackages();
		
		Core::fireEvent('pageshow');
		
		if($this->substitution) $this->assigned = $this->assigned[$this->substitution];
		
		$out = $ContentType->process($this->assigned);
		
		if(count($this->file)){
			$this->assigned = $out;
			
			$out = parent::parse(true);
		}
		
		Response::sendHeaders();
		
		if($ContentType->getType()=='html')
			$out = preg_replace('/(<pre.*?>[\s\S]*?<\/pre>)|\t+/i', '$1', $out);
		
		if($return) return $out;
		
		echo $out;
		flush();
	}
	
}