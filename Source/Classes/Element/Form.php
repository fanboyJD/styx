<?php
class Form extends Elements {
	
	public function __construct(){
		parent::__construct(func_get_args(), get_class());
		
		if(!$this->options['method'])
			$this->options['method'] = 'post';
	}
	
	public function format($tpl = null){
		return '<form'.$this->implode('skipName').'>'.parent::format($tpl).'</form>';
	}
	
	//this method needs to be redone!
	public function getFields($options = array(
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
					$els[$el->options['name']] = array_extend($els[$el->options['name']], $el->options['jsvalidate']);
			}else
				$els[] = $el->options['name'];
		}
		return $els;
	}
	
	public function getEvents($helper){
		$els = array();
		
		foreach($this->elements as $el)
			$els[] = $el->getEvents($helper);
		
		return implode($els);
	}
	
	public function setValue($data){
		foreach($this->elements as $el)
			if($data[$el->options['name']])
				$el->setValue($data[$el->options['name']]);
	}
	
	public function getValue(){
		$els = array();
		
		foreach($this->elements as $el)
			if($val = $el->getValue())
				$els[$el->options['name']] = $val;
		
		return sizeof($els) ? $els : false;
	}
	
	public function prepare($data, $alias = false){
		$els = array();
		
		foreach($this->elements as $el){
			if($el->type=='button' || (!$alias && ($el->options[':alias'] || $el->options[':readOnly'])) || ($alias && !$this->options[':alias']))
				continue;
			
			$val = $el->formatData($data[$el->options['name']]);
			
			if($val!==false && !is_null($val))
				$els[$el->options['name']] = $el->setValue($val);
		}
		
		return sizeof($els) ? $els : false;
	}
	
	public function validate($data){
		foreach($this->elements as $el){
			unset($v);
			if(!in_array($el->type, self::$formElements) || !$el->options[':validate'])
				continue;
			
			$val = Data::clean($data[$el->options['name']]);
			if(!$val){
				if(!$el->options[':empty'] && $el->options[':validate'][0]!='bool')
					return array($el->options['name'], 'notempty');
				elseif($el->options[':empty'] || $el->options[':validate'][0]=='bool')
					continue;
			}elseif($this->options[':length'] && (strlen((string)$val)<$this->options[':length'][0] || strlen((string)$val)>$this->options[':length'][1]))
				return array($el->options['name'], 'length');
			
			$v = Validator::call($val, $el->options[':validate']);
			
			if($v!==true)
				return array($el->options['name'], $el->options[':validate'][0]);
		}
		
		return true;
	}
}
?>