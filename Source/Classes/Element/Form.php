<?php
/*
 * Styx::Form - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Holds different Elements and executes methods on them
 *
 */

class Form extends Elements {
	
	public function __construct(){
		parent::__construct(func_get_args(), get_class());
		
		if(empty($this->options['method']))
			$this->options['method'] = 'post';
	}
	
	public function format(){
		static $prefix = false;
		
		if($this->hasInstanceOf('UploadInput')) // Sets the enctype if needed and if hasn't been manually set yet
			$this->get('enctype', 'multipart/form-data');
		
		$out = array('form.top' => '<form'.$this->implode('skipName').'><div>');
		Hash::extend($out, parent::format());
		$out['form.bottom'] = '</div></form>';
		
		if($prefix===false)
			$prefix = pick(Core::retrieve('elements.prefix'));
		
		return $prefix ? array($prefix => $out) : $out;
	}
	
	public function prepare($alias = false){
		$els = array();
		
		foreach($this->elements as $k => $el){
			if($el->type=='button' || ($alias xor !empty($el->options[':alias'])))
				continue;
			
			$els[$k] = $el->prepare();
		}
		
		return $els;
	}
	
	public function validate(){
		foreach($this->elements as $k => $el){
			if(!in_array($el->type, self::$visibleElements) || empty($el->options[':validate']))
				continue;
			
			$v = Validator::call($el->getValue(), $el->options[':validate']);
			
			if($v!==true) return array($v, !empty($el->options[':caption']) ? $el->options[':caption'] : null);
		}
		
		return true;
	}
	
}