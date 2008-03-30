<?php
class Template {
	private $assigned = array(),
		$dir = null,
		$file = null;
	
	private static $paths = null;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function map($dir, $file){
		if(!self::$paths)
			self::$paths = array(
				'root' => realpath(Core::retrieve('path').'Templates/'),
				'appRoot' => realpath(Core::retrieve('appPath').'Templates/'),
			);
		
		$t = new Template();
		
		return $t->setFile($dir, $file);
	}
	
	public function setFile($dir, $file){
		$this->dir = $dir;
		$this->file = $file;
		return $this;
	}
	
	private function getFile(){
		/* @var $c cache */
		$c = Cache::getInstance();
		
		$file = $this->dir.'/'.$this->file.'.tpl';
		
		if(!Core::retrieve('debugMode'))
			$content = $c->retrieve('Templates', $file);
		
		if(!$content){
			$loadFile = self::$paths['appRoot'].$file;
			if(!file_exists($loadFile))
				$loadFile = self::$paths['root'].$file;
			
			$content = file_get_contents($loadFile);
			$c->store('Templates', $file, $content, ONE_WEEK);
		}
		return $content;
	}
	
	public function parse($return = false){
		$out = $this->getFile();
		
		$this->assigned = Util::multiImplode($this->assigned);
		
		preg_match_all('/\\${([A-z0-9\-_\.]+?)\}/i', $out, $vars);
		
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
	
	public function assign($array){
		$this->assigned = is_array($array) ? array_merge($this->assigned, $array) : $this->assigned;
		return $this;
	}
}
?>