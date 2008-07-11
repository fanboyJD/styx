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
	
	//this method needs to be redone!
	/*public function getFields($options = array(
		'all' => false,
		'detail' => true,
		'db' => false,
		'js' => false,
	)){
		$els = array();
		foreach($this->elements as $el){
			if($el->type=='button' || (!$options['all'] && !in_array($el->type, self::$formElements)))
				continue;
			if(!$options['detail'] && !$options['js'] && $el->options['detail'])
				continue;
			if($options['db'] && $el->options['alias'])
				continue;
			if($el->options['readOnly'] && !$options['db'])
				continue;
			if(in_array($el->options['name'], $els))
				continue;
			if($options['js']){
				$els[$el->options['name']] = array();
				if($el->options['validate'] && !in_array($el->options['validate'][0], array('specialchars', 'bool', 'pagetitle')))
					$els[$el->options['name']][] = $el->options['validate'];
				elseif($el->options['validate'][0]=='bool')
					$els[$el->options['name']][] = array('numericrange', array(0, 1));
				
				if($el->options['length'])
					$els[$el->options['name']][] = array('length', $el->options['length']);
				if(!$el->options['empty'] && $el->options['validate'][0]!='bool')
					$els[$el->options['name']][] = 'notempty';
				if($el->options['jsvalidate'])
					$els[$el->options['name']] = Hash::extend($els[$el->options['name']], $el->options['jsvalidate']);
			}else
				$els[] = $el->options['name'];
		}
		return $els;
	}*/
	
	public function getEvents($helper){
		$els = array();
		
		foreach($this->elements as $el)
			$els[] = $el->getEvents($helper);
		
		return implode($els);
	}
	
	public function setValue($data, $raw = false){
		foreach($data as $k => $v)
			if($this->elements[$k]){
				$el = $this->elements[$k];
				if($raw && (!in_array($el->type, self::$formElements) || $el->options[':readOnly']))
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
			if($el->type=='button' || (!$alias && ($el->options[':alias'] || $el->options[':readOnly'])) || ($alias && !$this->options[':alias']))
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
				if(!$el->options[':empty'] && $el->options[':validate'][0]!='bool' && $el->options[':preset'])
					return array('notempty', $k, $el->options[':caption']);
				elseif($el->options[':empty'] || $el->options[':validate'][0]=='bool')
					continue;
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