<?php
class Form extends Elements {
	
	protected static $prefix = false;
	
	public function __construct(){
		parent::__construct(func_get_args(), get_class());
		
		if(!$this->options['method'])
			$this->options['method'] = 'post';
	}
	
	public function format(){
		$out = array('form.top' => '<form'.$this->implode('skipName').'>');
		Hash::extend($out, parent::format());
		$out['form.bottom'] = '</form>';
		
		if(self::$prefix===false)
			self::$prefix = pick(Core::retrieve('elements.prefix'), null);
		
		return self::$prefix ? array(self::$prefix => $out) : $out;
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
		foreach($data as $k => $v)
			if($this->elements[$k]){
				$el = $this->elements[$k];
				if($raw && (!in_array($el->type, self::$formElements) || $el->options[':alias']))
					continue;
				
				$el->setValue(Data::clean($v));
			}
	}
	
	public function getValue($name){
		return $this->elements[$name] ? $this->elements[$name]->getValue() : false;
	}
	
	public function prepareData($alias = false){
		$els = array();
		
		foreach($this->elements as $k => $el){
			if($el->type=='button' || $el->options[':readOnly'] || ($alias xor $el->options[':alias']))
				continue;
			
			$val = $el->getValue();
			
			if($el->options[':validate'][0])
				$val = Data::call($val, $el->options[':validate']);
			
			$els[$k] = $val;
		}
		
		return sizeof($els) ? $els : false;
	}
	
	public function validate(){
		foreach($this->elements as $k => $el){
			unset($v);
			if(!in_array($el->type, self::$formElements))
				continue;
			
			$val = $el->getValue();
			if(!$val){
				if($el->options[':empty'] || $el->options[':validate'][0]=='bool' || ($el->options[':validate'][0]=='numericrange' && $el->options[':validate'][1][0]==0))
					continue;
				elseif(!$el->options[':empty'])
					return array('notempty', $k, $el->options[':caption']);
			}elseif($this->options[':length'] && (strlen((string)$val)<$this->options[':length'][0] || strlen((string)$val)>$this->options[':length'][1]))
				return array('length', $k, $el->options[':caption']);
			
			if(!$el->options[':validate'][0])
				continue;
			
			$v = Validator::call($val, $el->options[':validate']);
			
			if($v!==true) return array($el->options[':validate'][0], $k, $el->options[':caption']);
		}
		
		return true;
	}
}