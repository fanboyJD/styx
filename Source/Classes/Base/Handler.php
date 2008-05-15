<?php
class Handler extends Template {
	
	private static $Instances = array(
			'master' => null,
			'slaves' => array(),
		),
		$Type = null,
		$Types = array(
			'html' => array(
				'headers' => array(
					'Content-Type' => 'text/html; charset=utf8',
				),
				'defaultCallback' => 'implode', // For the sake of php inconsistency this works as we want
			),
			'json' => array(
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf8',
				),
				'callback' => 'json_encode',
			),
			'xml' => array(
				'headers' => array(
					'Content-Type' => 'application/xhtml+xml; charset=utf8',
				),
			),
		);
	
	private $parsed = null,
		$name = null,
		$master = false,
		$base = 'Handler',
		$enabled = true;
	
	protected function __construct($name = null){
		$this->name = $name;
		if(!$name) $this->master = true;
		
		$this->object($this);
	}
	
	/**
	 * @param string $name
	 * @return Handler
	 */
	public static function map($name = null){
		if(!$name) $type = $name = 'master';
		else $type = 'slaves';
		
		if(!self::$Instances[$type][$name])
			self::$Instances[$type][$name] = new Handler($name=='master' ? null : $name);
		
		return self::$Instances[$type][$name];
	}
	
	public static function setHeader($name, $value = null){
		if(is_array($name)){
			foreach($name as $k => $v)
				self::setHeader($k, $v);
			
			return;
		}
		
		try{
			header($name.': '.$value);
		}catch(Exception $e){}
	}
	
	public static function setType($type = null){
		if(self::$Type)
			return;
		
		$type = strtolower($type);
		if(!self::$Types[$type]){
			reset(self::$Types);
			$type = key(self::$Types);
		}
		
		self::$Type = $type;
	}
	
	public static function setHandlers($handlers){
		foreach(self::$Types as $k => $v)
			if(!in_array($k, $handlers))
				unset(self::$Types[$k]);
	}
	
	public static function behaviour(){
		$types = func_get_args();
		foreach($types as $v)
			if(self::$Type==$v)
				return true;
		
		return false;
	}
	
	/**
	 * @return Handler
	 */
	public function disable(){
		$this->enabled = false;
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function enable(){
		$this->enabled = true;
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function setBase($base){
		$this->base = func_get_args();
		if(sizeof($this->base)==1) $this->base = splat($this->base[0]);
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function setTemplate(){
		$args = func_get_args();
		if(sizeof($args)==1) $args = splat($args[0]);
		
		foreach(array_reverse(splat($this->base)) as $v)
			array_unshift($args, $v);
		
		return $this->initialize($args);
	}
	
	/**
	 * @return Handler
	 */
	public function filter($string){
		if(is_array($string)){
			foreach($string as $v)
				$this->filter($v);
			
			return $this;
		}
		
		foreach($this->assigned as $k => &$val)
			if(!startsWith($k, $string))
				unset($val);
		
		return $this;
	}
	
	public function callback($callback = 'callback'){
		$c = self::$Types[self::$Type][$callback];
		if($c && ((is_array($c) && method_exists($c[0], $c[1])) || function_exists($c)))
			$this->parsed = call_user_func($c, $this->assigned);
	}
	
	public function parse($return = false){
		if(!$this->enabled)
			return;
		
		if($this->master){
			self::setHeader(self::$Types[self::$Type]['headers']);
			
			foreach(self::$Instances['slaves'] as $k => $v)
				$assign[$k] = $v->parse(true);
			
			$main = Route::getMainLayer();
			if($main) $assign['layer'] = $assign['layer.'.$main];
			
			$this->assign($assign);
		}
		
		$this->callback();
		
		if(sizeof($this->file)){
			if(is_array($this->parsed)) $this->assign($this->parsed);
			
			return parent::parse($return);
		}else{
			$this->callback('defaultCallback');
		}
		
		if($return) return $this->parsed;
		
		echo $this->parsed;
		flush();
	}
	
}
?>