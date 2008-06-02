<?php
class Template extends Runner {
	
	protected $assigned = array(),
		$file = array(),
		$obj = null;
	
	protected static $init = null;
	
	protected function __construct(){}
	protected function __clone(){}
	
	protected function initialize($file){
		if(!self::$init)
			self::$init = array(
				'root' => realpath(Core::retrieve('path').'Templates/'),
				'app.root' => realpath(Core::retrieve('app.path').'Templates/'),
				'tpl.standard' => Core::retrieve('tpl.standard'),
				'tpl.execute' => Core::retrieve('tpl.execute'),
			);
		
		$this->file = $file;
		
		return $this;
	}
	
	protected static function getFileName($file){
		$loadFile = self::$init['app.root'].'/'.$file;
		if(!file_exists($loadFile)){
			$loadFile = self::$init['root'].'/'.$file;
			if(!file_exists($loadFile))
				return false;
		}
		
		return $loadFile;
	}
	
	protected function getFile(){
		$c = Cache::getInstance();
		
		$ext = pathinfo(end($this->file), PATHINFO_EXTENSION);
		$file = implode('/', $this->file).(!$ext ? $ext = '.'.self::$init['tpl.standard'] : '');
		
		if(in_array($ext, self::$init['tpl.execute'])){
			if($this->obj && method_exists($this->obj, 'execute')){
				ob_start();
				
				$filename = self::getFileName($file);
				if(!$filename) return;
				
				$this->obj->execute($filename);
				return ob_get_clean();
			}else{
				// We stop here if the extension is not a template for 
				// security reasons (code may gets exposed)
				return;
			}
		}
		
		if(!Core::retrieve('debug')){
			$tpl = $c->retrieve('Templates', $file);
			if($tpl) return $tpl;
		}
		
		$filename = self::getFileName($file);
		if(!$filename) return;
		
		return $c->store('Templates', $file, file_get_contents($filename), ONE_WEEK);
	}
	
	/**
	 * @return Template
	 */
	public static function map(){
		$args = func_get_args();
		if(sizeof($args)==1) $args = splat($args[0]);
		
		$instance = new Template();
		return $instance->initialize($args);
	}
	
	/**
	 * @return Template
	 */
	public function object($obj){
		if(is_object($obj)) $this->obj = $obj;
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function assign($array){
		$this->assigned = array_extend($this->assigned, splat($array));
		
		return $this;
	}
	
	public function parse($return = false){
		$out = $this->getFile();
		
		array_flatten($this->assigned);
		
		preg_match_all('/\\$\{([\w\.:]+)\}/i', $out, $vars);
		
		$rep = array(array_values($vars[0]));
		foreach($vars[1] as $val)
			$rep[1][] = $this->assigned[$val];
		
		$rep[0][] = "\t";
		$rep[1][] = '';
		
		$out = str_replace($rep[0], $rep[1], $out);
		
		if($return) return $out;
		
		echo $out;
		flush();
	}
}