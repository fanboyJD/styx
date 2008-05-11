<?php
class Handler extends Runner {
	
	private static $Master,
		$Instances = array();
	
	/**
	 * Template Class
	 *
	 * @var Template
	 */
	private $template = null;
	
	private static $type = null,
		$handlers = array(
			'html' => array(
				'headers' => array(
					'Content-Type' => 'text/html; charset=utf8',
				),
				'defaultCallback' => 'implode',
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
	
	private $data = array(),
		$parsed = null,
		$name = null,
		$master = false,
		$enabled = true;
	
	private function __construct($name = null){
		$this->name = $name;
		if(!$name) $this->master = true;
	}
	private function __clone(){}
	
	/**
	 * @param string $name
	 * @return Handler
	 */
	public static function getInstance($name = null){
		if(!$name){
			if(!self::$Master)
				self::$Master = new Handler();
			
			return self::$Master;
		}
		
		if(!self::$Instances[$name])
			self::$Instances[$name] = new Handler($name);
		
		return self::$Instances[$name];
	}
	
	private static function setHeader($name, $value = null){
		if(is_array($name)){
			foreach($name as $k => $v)
				self::setHeader($k, $v);
			
			return;
		}
		
		header($name.': '.$value);
	}
	
	public static function setType($type = null){
		if(self::$type)
			return;
		
		$type = strtolower($type);
		if(!self::$handlers[$type]){
			reset(self::$handlers);
			$type = key(self::$handlers);
		}
		
		self::$type = $type;
	}
	
	public static function setHandlers($handlers){
		foreach(self::$handlers as $k => $v)
			if(!in_array($k, $handlers))
				unset(self::$handlers[$k]);
	}
	
	/**
	 * @return Handler
	 */
	public function behaviour(){
		$types = func_get_args();
		foreach($types as $v)
			if(self::$type==$v)
				return $this;
		
		return new EmptyClass();
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
	public function setTemplate($template){
		$this->template = Template::map('Handler', $template)->object($this);
		
		return $this;
	}
	
	/**
	 * @return Handler
	 */
	public function assign($array){
		$this->data = array_extend($this->data, splat($array));
		
		return $this;
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
		
		foreach($this->data as $k => $val)
			if(!startsWith($k, $string))
				unset($this->data[$k]);
		
		return $this;
	}
	
	public function parse($return = false){
		if(!$this->enabled)
			return;
		
		if($this->master){
			self::setHeader(self::$handlers[self::$type]['headers']);
			
			foreach(self::$Instances as $k => $v)
				$assign[$k] = $v->parse(true);
			
			$assign['layer'] = $assign['layer.'.Route::getMainLayer()];
			
			$this->assign($assign);
		}
		
		
		$callback = self::$handlers[self::$type]['callback'];
		if($callback && ((is_array($callback) && method_exists($callback[0], $callback[1])) || function_exists($callback)))
			$this->parsed = call_user_func($callback, $this->data);
		
		if($this->template){
			if(is_array($this->parsed)) $this->template->assign($this->parsed);
			
			return $this->template->assign($this->data)->parse($return);
		}else{
			$callback = self::$handlers[self::$type]['defaultCallback'];
			if($callback && ((is_array($callback) && method_exists($callback[0], $callback[1])) || function_exists($callback)))
				$this->parsed = call_user_func($callback, $this->data);
		}
		
		if($return)
			return $this->parsed;
		
		echo $this->parsed;
		flush();
	}
	
}
?>