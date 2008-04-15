<?php
class Form extends Elements {
	
	public function __construct(){
		parent::__construct(func_get_args());
	}
	
	public function format($tpl = null, $vars = array()){
		return '<div'.$this->implode('skipName').'>'.parent::format($tpl, $vars).'</div>';
	}
	
	//this method needs to be redone!
	public function getFields($options = array(
		'all' => false,
		'detail' => true,
		'db' => false,
		'js' => false,
	)){
		foreach($this->elements as $el){
			if(in_array($el->type, array('button', 'string')) || (!$options['all'] && !in_array($el->type, self::$formElements)))
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
	
	public function prepareElementData($val, $el){
		if($el->type=='field')
			$val = $el->options['value'];
		
		if($el->options[':length'][1])
			$val = substr($val, 0, $el->options[':length'][1]);
		
		if($el->options[':validate'])
			$val = parser::call($el->options[':validate'], $val);
		
		return db::add(Util::cleanWhitespaces($val));
	}
	
	public function prepareData($data, $alias = false){
		$els = array();
		
		foreach($this->elements as $el){
			if(in_array($el->type, array('button', 'string')) || (!$alias && ($el->options[':alias'] || $el->options[':readOnly'])) || ($alias && !$this->options[':alias']))
				continue;
			
			$val = $this->prepareElementData($data[$el->options['name']], $el);
			
			if($val!==false)
				$els[$el->options['name']] = $val;
		}
		
		return $els;
	}
	
	public function validate($data){
		$return = true;
		
		foreach($this->elements as $el){
			if(!in_array($el->type, self::$formElements) || !$el->options[':validate'])
				continue;
			
			$data = Util::cleanWhitespaces($data[$el->options['name']]);
			if(!$el->options[':empty'] && $el->options[':validate'][0]!='bool' && !$data){
				$return = 'notempty';
				break;
			}
			
			if(($el->options[':empty'] || $el->options[':validate'][0]=='bool') && !$data){
				$v = true;
			}else{
				$v = Validator::call($el->options[':validate'], $data);
				if($this->options[':length'] && $v===true && (strlen($data)<$this->options[':length'][0] || strlen($data)>$this->options[':length'][1]))
					$v = false;
			}
			if($v!==true){
				$return = $v;
				break;
			}
		}
		
		return $return;
	}
}
?>