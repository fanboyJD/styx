<?php
/*
 * Styx::Template - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses files and replaces their contents with dynamic data
 *
 */


class Template extends Runner {
	
	protected $assigned = null,
		$file = array(),
		$bind = null;
	
	/**
	 * @return Template
	 */
	public static function map(){
		$args = Hash::args(func_get_args());
		
		return new Template($args);
	}
	
	protected function __construct(){
		$args = Hash::args(func_get_args());
		
		if($args) $this->apply($args);
	}
	
	protected static function getFileName($file){
		static $Paths;
		if(!$Paths) $Paths = array(
				'app.root' => realpath(Core::retrieve('app.path').'Templates/'),
				'root' => realpath(Core::retrieve('path').'Templates/'),
			);
		
		foreach(array(
			$Paths['app.root'].'/'.$file,
			$Paths['root'].'/'.$file
		) as $f)
			if(file_exists($f))
				return $f;
		
		return false;
	}
	
	protected function getFile(){
		static $Configuration;
		if(!$Configuration) $Configuration = array(
				'template.default' => Core::retrieve('template.default'),
				'template.execute' => Core::retrieve('template.execute'),
			);
		
		$c = Cache::getInstance();
		
		$ext = pathinfo(end($this->file), PATHINFO_EXTENSION);
		$file = implode('/', $this->file).(!$ext ? $ext = '.'.$Configuration['template.default'] : '');
		
		if(in_array($ext, $Configuration['template.execute'])){
			if($this->bind && method_exists($this->bind, 'execute')){
				ob_start();
				
				$filename = self::getFileName($file);
				if(!$filename) return;
				
				$this->bind->execute($filename);
				return ob_get_clean();
			}else{
				/* 
				 * We stop here if the extension is not a template for 
				 * security reasons (code may gets exposed)
				 */
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
	public function assign(){
		$args = Hash::args(func_get_args());
		
		if(Hash::length($args)==1 && isset($args[0]) && is_string($args[0])) $this->assigned = $args[0];
		else $this->assigned = Hash::extend($this->assigned, $args);
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function base(){
		$this->base = Hash::args(func_get_args());
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function apply(){
		$args = Hash::args(func_get_args());
		
		foreach(array_reverse(Hash::splat($this->base)) as $v)
			array_unshift($args, $v);
		
		$this->file = $args;
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function bind($bind){
		if(is_object($bind)) $this->bind = $bind;
		
		return $this;
	}
	
	/**
	 * Either echos/returns the fully parsed template or, in case there is no template, returns an array of the assigned values
	 *
	 * @param bool $return
	 * @return mixed
	 */
	public function parse($return = false){
		if(!Hash::length($this->file))
			return $this->assigned;
		
		$out = $this->getFile();
		if(!$out && $return) return $this->assigned;
		
		Hash::splat($this->assigned);
		Hash::flatten($this->assigned);
		
		preg_match_all('/\\$\{([A-z0-9\.:\s|]+)\}/i', $out, $vars);
		
		$rep = array(array_values($vars[0]));
		$i = 0;
		foreach($vars[1] as $v){
			$v = Data::clean(explode('|', $v));
			foreach(Hash::splat($v) as $val){
				if(!empty($this->assigned[$val])){
					$rep[1][$i] = $this->assigned[$val];
					break;
				}elseif(String::starts($val, 'lang.') && $lang = Lang::retrieve(substr($val, 5))){
					$rep[1][$i] = $lang;
					break;
				}
			}
			
			if(empty($rep[1][$i])) $rep[1][$i] = '';
			
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