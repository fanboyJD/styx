<?php
class Template {
	private $assigned = array(),
		$file = array(),
		$obj = null;
	
	private static $paths = null;
	
	private function __construct($file){
		$this->file = $file;
	}
	private function __clone(){}
	
	private static function getFileName($file){
		$loadFile = self::$paths['appRoot'].$file;
		if(!file_exists($loadFile)){
			$loadFile = self::$paths['root'].$file;
			if(!file_exists($loadFile))
				return false;
		}
		
		return $loadFile;
	}
	
	private function getFile(){
		$c = Cache::getInstance();
		
		$ext = pathinfo(end($this->file), PATHINFO_EXTENSION);
		$file = implode('/', $this->file).(!$ext ? $ext = '.tpl' : '');
		
		$allowExecution = in_array($ext, array('php', 'phtml'));
		
		if($allowExecution){
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
		
		if(!Core::retrieve('debugMode'))
			return $c->retrieve('Templates', $file);
		
		
		$filename = self::getFileName($file);
		if(!$filename) return;
		
		return $c->store('Templates', $file, file_get_contents($filename), ONE_WEEK);
	}
	
	/**
	 * @param mixed $file
	 * @return Template
	 */
	public static function map($file){
		if(!self::$paths)
			self::$paths = array(
				'root' => realpath(Core::retrieve('path').'Templates/'),
				'appRoot' => realpath(Core::retrieve('appPath').'Templates/'),
			);
		
		$args = func_get_args();
		if(sizeof($args)==1 && !is_array($file))
			$args = splat($file);
		
		return new Template($args);
	}
	
	/**
	 * @return Template
	 */
	public function object($obj){
		if(is_object($obj))
			$this->obj = $obj;
		
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
		
		preg_match_all('/\\${([A-z0-9\-_\.\:]+?)\}/i', $out, $vars);
		
		$rep = array(
			array_values($vars[0]),
		);
		foreach($vars[1] as $val)
			$rep[1][] = $this->assigned[$val];
		
		$rep[0][] = "\t";
		$rep[1][] = "";
		
		$out = str_replace($rep[0], $rep[1], $out);
		
		if($return)
			return $out;
		
		echo $out;
		flush();
	}
}
?>