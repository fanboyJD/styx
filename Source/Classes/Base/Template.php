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
		$appended = array(),
		$file = array(),
		$bound = null;
	
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
	
	protected function getFile(){
		static $Configuration;
		if(!$Configuration)
			$Configuration = Core::fetch('Templates', 'template.default', 'template.execute');
		
		$ext = pathinfo(end($this->file), PATHINFO_EXTENSION);
		$file = implode('/', $this->file).(!$ext ? '.'.$Configuration['template.default'] : '');
		
		if(in_array(strtolower($ext ? $ext : $Configuration['template.default']), $Configuration['template.execute'])){
			if($this->bound && method_exists($this->bound, 'execute')){
				if(empty($Configuration['Templates'][$file])) return;
				
				ob_start();
				$this->bound->execute($this->assigned, $Configuration['Templates'][$file]);
				return ob_get_clean();
			}else{
				/* 
				 * We stop here if the extension is not a template for 
				 * security reasons (code may gets exposed)
				 */
				return;
			}
		}
		
		return !empty($Configuration['Templates'][$file]) ? file_get_contents($Configuration['Templates'][$file]) : false;
	}
	
	public function hasFile(){
		return !!Hash::length($this->file);
	}
	
	/**
	 * @return Template
	 */
	public function assign(){
		$args = Hash::args(func_get_args());
		
		Hash::extend($this->assigned, $args);
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function append($value, $unshift = false){
		$fn = 'array_'.($unshift ? 'unshift' : 'push');
		$fn($this->appended, $value);
		
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
		if(is_object($bind)) $this->bound = $bind;
		
		return $this;
	}
	
	/**
	 * Either echos/returns the fully parsed template or, in case there is no template, returns an array of the assigned values
	 *
	 * @param bool $return
	 * @return mixed
	 */
	public function parse($return = false){
		static $Configuration;
		
		if(!$Configuration) $Configuration = Core::fetch('template.regex', 'template.striptabs');
		
		if(!$this->hasFile()) return count($this->appended) ? implode($this->appended) : $this->assigned;
		
		$out = $this->getFile();
		if(!$out && $return) return count($this->appended) ? implode($this->appended) : $this->assigned;
		
		Hash::flatten($this->assigned);
		
		preg_match_all($Configuration['template.regex'], $out, $vars);
		
		$rep = array(array_values($vars[0]), array());
		$i = 0;
		foreach($vars[1] as $v){
			$vals = array_map('trim', explode('|', $v));
			if($vals){
				foreach($vals as $val){
					if(!empty($this->assigned[$val])){
						$rep[1][$i++] = $this->assigned[$val];
						continue 2;
					}elseif(String::starts($val, 'lang.') && $lang = Lang::retrieve(substr($val, 5))){
						$rep[1][$i++] = $lang;
						continue 2;
					}
				}
			}
			
			if(empty($rep[1][$i])) $rep[1][$i] = '';
			
			$i++;
		}
		
		if(!empty($Configuration['template.striptabs'])){
			$rep[0][] = "\t";
			$rep[1][] = '';
		}
		
		$out = str_replace($rep[0], $rep[1], $out);
		
		if($return) return $out;
		
		echo $out;
		flush();
	}
}