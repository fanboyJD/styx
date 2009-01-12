<?php
/*
 * Styx::Form - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Holds different Elements and executes methods on them
 *
 */

class Form extends Elements {
	
	protected static $formElements = array('input', 'checkbox', 'radio', 'select', 'textarea', 'richtext', 'optionlist');
	
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
	
	/**
	 * Sets value for elements inside the given form.
	 * If the second parameter is set true it does not set hidden elements like
	 * field-elements
	 *
	 * @param array $data
	 * @param bool $raw
	 */
	public function setValue($data, $raw = false){
		if(!is_array($data)) return;
		
		foreach($data as $k => $v)
			if(!empty($this->elements[$k])){
				$el = $this->elements[$k];
				if($raw && !in_array($el->type, self::$formElements))
					continue;
				
				$el->setValue($v);
			}
	}
	
	public function getValue($name){
		return !empty($this->elements[$name]) ? $this->elements[$name]->getValue() : false;
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
			if(!in_array($el->type, self::$formElements) || empty($el->options[':validate']))
				continue;
			
			$v = Validator::call($el->getValue(), $el->options[':validate']);
			
			if($v!==true) return array($v, !empty($el->options[':caption']) ? $el->options[':caption'] : null);
		}
		
		return true;
	}
	
}