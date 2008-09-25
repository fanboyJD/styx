<?php
/*
 * Styx::Template - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses files and replaces their contents with dynamic data
 *
 */


class Template extends Runner {
	
	protected $assigned = array(),
		$file = array(),
		$bind = null;
	
	protected static $Configuration = null;
	
	protected function __construct(){}
	protected function __clone(){}
	
	protected function initialize($file){
		if(!self::$Configuration)
			self::$Configuration = array(
				'root' => realpath(Core::retrieve('path').'Templates/'),
				'app.root' => realpath(Core::retrieve('app.path').'Templates/'),
				'tpl.standard' => Core::retrieve('tpl.standard'),
				'tpl.execute' => Core::retrieve('tpl.execute'),
			);
		
		$this->file = $file;
		
		return $this;
	}
	
	protected static function getFileName($file){
		$loadFile = self::$Configuration['app.root'].'/'.$file;
		if(!file_exists($loadFile)){
			$loadFile = self::$Configuration['root'].'/'.$file;
			if(!file_exists($loadFile))
				return false;
		}
		
		return $loadFile;
	}
	
	protected function getFile(){
		$c = Cache::getInstance();
		
		$ext = pathinfo(end($this->file), PATHINFO_EXTENSION);
		$file = implode('/', $this->file).(!$ext ? $ext = '.'.self::$Configuration['tpl.standard'] : '');
		
		if(in_array($ext, self::$Configuration['tpl.execute'])){
			if($this->bind && method_exists($this->bind, 'execute')){
				ob_start();
				
				$filename = self::getFileName($file);
				if(!$filename) return;
				
				$this->bind->execute($filename);
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
		$args = Hash::args(func_get_args());
		
		$instance = new Template();
		return $instance->initialize($args);
	}
	
	/**
	 * @return Template
	 */
	public function bind($bind){
		if(is_object($bind)) $this->bind = $bind;
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function assign(){
		$args = func_get_args();
		$this->assigned = Hash::extend($this->assigned, Hash::args($args));
		
		return $this;
	}
	
	public function parse($return = false){
		$out = $this->getFile();
		
		Hash::flatten($this->assigned);
		
		preg_match_all('/\\$\{([A-z0-9\.:\s|]+)\}/i', $out, $vars);
		
		$rep = array(array_values($vars[0]));
		$i = 0;
		foreach($vars[1] as $v){
			foreach(Hash::splat(Data::clean(explode('|', $v))) as $val){
				if($this->assigned[$val]){
					$rep[1][$i] = $this->assigned[$val];
					break;
				}elseif(String::starts($val, 'lang.') && $lang = Lang::retrieve(substr($val, 5))){
					$rep[1][$i] = $lang;
					break;
				}
			}
			
			if(!$rep[1][$i]) $rep[1][$i] = '';
			
			$i++;
		}
		
		$rep[0][] = "\t";
		$rep[1][] = '';
		
		$out = str_replace($rep[0], $rep[1], $out);
		
		if($return) return $out;
		
		echo $out;
		flush();
	}
}