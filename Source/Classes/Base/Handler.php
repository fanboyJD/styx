<?php
class Handler {
	
	private static $Instance;
	
	/**
	 * Template Class
	 *
	 * @var Template
	 */
	private $template = null;
	
	private $handlers = array(
			'html' => array(
				'headers' => array(
					'Content-Type' => 'text/html; charset=utf8',
				),
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
		),
		$data = array(),
		$type = null,
		$parsed = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function getInstance(){
		if(!self::$Instance)
			self::$Instance = new Handler();
		
		return self::$Instance;
	}
	
	public function behaviour($type){
		if($this->type==$type) return $this;
		
		return new EmptyClass();
	}
	
	public function setType($type){
		$type = strtolower($type);
		if(!$this->handlers[$type]){
			reset($this->handlers);
			$type = key($this->handlers);
		}
		
		$this->type = $type;
		
		$this->setHeader($this->handlers[$this->type]['headers']);
	}
	
	public function setHeader($name, $value = null){
		if(is_array($name)){
			foreach($name as $k => $v)
				$this->setHeader($k, $v);
			
			return;
		}
		
		header($name.': '.$value);
	}
	
	public function setTemplate($template){
		$this->template = Template::map('Handler', $template)->object($this);
		
		return $this;
	}
	
	public function assign($array){
		$this->data = array_extend($this->data, splat($array));
		
		return $this;
	}
	
	public function filter($string){
		if(is_array($string)){
			foreach($string as $v)
				$this->filter($v);
			
			return $this;
		}
		foreach($this->data as $k => &$val)
			if(!startsWith($k, $string))
				unset($this->data[$k]);
		
		return $this;
	}
	
	public function parse(){
		$callback = $this->handlers[$this->type]['callback'];
		if($callback && ((is_array($callback) && method_exists($callback[0], $callback[1])) || function_exists($callback)))
			$this->parsed = call_user_func($callback, $this->data);
		
		if($this->template){
			if(is_array($this->parsed)) $this->template->assign($this->parsed);
			return $this->template->assign($this->data)->parse();
		}
		
		echo $this->parsed;
		flush();
	}
	
	public function execute(){
		include(func_get_arg(0));
	}
}
?>