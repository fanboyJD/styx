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
		$base = array(),
		$bound = null;
	
	protected function __construct($file = null){
		if(is_array($file)) $this->file = $file;
	}
	
	/**
	 * @return Template
	 */
	public static function map(){
		$args = Hash::args(func_get_args());
		
		return new Template($args);
	}
	
	protected function getFile(){
		static $Configuration;
		if(!$Configuration) $Configuration = Core::fetch('Templates', 'debug', 'template.regex', 'template.default', 'template.execute');
		
		$file = $this->file;
		foreach(array_reverse(Hash::splat($this->base)) as $v)
			array_unshift($file, $v);
		
		$info = pathinfo(implode('/', $file));
		if(empty($info['extension'])) $info['extension'] = $Configuration['template.default'];
		$file = $info['dirname'].'/'.$info['filename'].'.'.$info['extension'];
		
		if(empty($Configuration['Templates'][$file])) return;
		
		$execute = in_array(strtolower($info['extension']), $Configuration['template.execute']) && $this->bound && method_exists($this->bound, 'execute');
		
		if(!$execute){
			$c = Cache::getInstance();
			
			$array = $c->retrieve('Templates/'.$file);
			if($array && !$Configuration['debug']) return $array;
			
			$content = file_get_contents($Configuration['Templates'][$file]);
			if(!$content) return false;
			
			preg_match_all($Configuration['template.regex'], $content, $matches);
			
			return $c->store('Templates/'.$file, array(
				'content' => $content,
				'matches' => $matches,
			), ONE_DAY);
		}
		
		ob_start();
		$this->bound->execute($this->assigned, $Configuration['Templates'][$file]);
		$content = ob_get_clean();
		
		if(!$content) return;
	
		preg_match_all($Configuration['template.regex'], $content, $matches);
		
		return array(
			'content' => $content,
			'matches' => $matches,
		);
	}
	
	public function hasFile(){
		return !!count($this->file);
	}
	
	/**
	 * @return Template
	 */
	public function assign($assign){
		Hash::extend($this->assigned, is_object($assign) ? $assign->toArray() : $assign);
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function prepend($value){
		array_unshift($this->appended, $value);
		
		return $this;
	}
	
	/**
	 * @return Template
	 */
	public function append($value){
		array_push($this->appended, $value);
		
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
		$this->file = Hash::args(func_get_args());
		
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
		
		if(!$Configuration) $Configuration = Core::fetch('template.striptabs');
		
		if(!$this->hasFile()) return count($this->appended) ? implode($this->appended) : $this->assigned;
		
		$array = $this->getFile();
		if(!$array && $return) return count($this->appended) ? implode($this->appended) : $this->assigned;
		
		Hash::flatten($this->assigned);
		
		$rep = array(array_values($array['matches'][0]), array());
		$i = 0;
		foreach($array['matches'][1] as $v){
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
		
		if($Configuration['template.striptabs']){
			$rep[0][] = "\t";
			$rep[1][] = '';
		}
		
		$array['content'] = str_replace($rep[0], $rep[1], $array['content']);
		
		if($return) return $array['content'];
		
		echo $array['content'];
		flush();
	}
}