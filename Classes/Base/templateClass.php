<?php
class Template {
	public $root = 'Templates/',
		$appRoot = null,
		$tpl = array(),
		$tmp = null;
	
	private static $instance;
	
	private function __construct(){
		$root = $this->root;
		
		$this->root = realpath(Env::retrieve('basePath').$root);
		$this->appRoot = realpath(Env::retrieve('appPath').$root);
		
		$this->tpl['global'] = array();
	}
	
	private function __clone(){}
	
	public static function getInstance($file = null){
		if(!self::$instance)
			self::$instance = new template();
		
		if($file)
			self::$instance->setFile($file);
		
		return self::$instance;
	}
	
	private function flush($file){
		unset($this->tpl[$file]);
		$this->tmp = null;
	}
	
	public function setFile($file){
		$this->tmp = $file;
		return $this;
	}
	
	private function getFile($file){
		/* @var $c cache */
		$c = Cache::getInstance();
		
		if(!Env::retrieve('debugMode'))
			$content = $c->retrieve('Templates', $file);
		
		if(!$content){
			$loadFile = $this->appRoot.$file.'.tpl';
			if(!file_exists($loadFile))
				$loadFile = $this->root.$file.'.tpl';
			
			$content = file_get_contents($loadFile);
			$c->store('Templates', $file, $content, ONE_WEEK);
		}
		return $content;
	}
	
	public function parse($return = false){
		if(!$this->tmp)
			return;
		
		$assigned = $this->tpl[$this->tmp] ? array_merge($this->tpl[$this->tmp], $this->tpl['global']) : $this->tpl['global'];
		
		$out = $this->getFile($this->tmp);
		
		preg_match_all('/\{([A-z0-9\-_]+?)\}/i', $out, $vars);
		$rep = array(
			array_values($vars[0]),
		);
		foreach($vars[1] as $val)
			$rep[1][] = $this->tpl[$this->tmp][$val];
		
		$rep[0][] = "\t";
		$rep[1][] = "";
		
		$out = str_replace($rep[0], $rep[1], $out);
		
		$this->flush($this->tmp);
		
		if($return)
			return $out;
		
		echo $out;
		flush();
	}
	
	public function assign($array, $file = null){
		if(!is_array($array))
			return $this;
		
		if($this->tmp && $file!='global')
			$file = $this->tmp;
		
		$this->tpl[$file] = $this->tpl[$file] ? array_merge($this->tpl[$file], $array) : $array;
		
		return $this;
	}
}
?>