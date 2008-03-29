<?php
class form extends element {
	private $elements = array();
	public function __construct(){
		$this->elements = func_get_args();
		if(is_array($this->elements[0]))
			$options = array_shift($this->elements);
		$this->collector = new elements($this->elements);
		parent::__construct($options, 'form');
	}
	public function get($tpl = null, $vars = array()){
		$this->html = $this->collector->get($tpl, $vars);
		$options = $this->options;
		unset($options['name']);
		return '<div '.self::implode($options).'>'.$this->html.'</div>';
	}
	public function getFields($options = array(
		'all' => false,
		'detail' => true,
		'db' => false,
		'js' => false,
	)){
		$els = array();
		foreach($this->elements as $el){
			if(in_array($el->type, array('button', 'string')) || (!$options['all'] && !in_array($el->type, array('input', 'checkbox', 'radio', 'select', 'textarea'))))
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
		if($el->options['length'][1])
			$val = substr($val, 0, $el->options['length'][1]);
		if($el->options['validate'])
			$val = parser::call($el->options['validate'], $val);
		return db::add(util::cleanWhitespaces($val));
	}
	public function prepareDatabaseData($data){
		$els = array();
		foreach($this->elements as $el){
			if(in_array($el->type, array('button', 'string')) || !$el->options['name'] || $el->options['alias'] || $el->options['readOnly'])
				continue;
			$val = $this->prepareElementData($data[$el->options['name']], $el);
			if($val!==false)
				$els[$el->options['name']] = $val;
		}
		return $els;
	}
	public function prepareAliasData($data){
		$els = array();
		foreach($this->elements as $el){
			if(!$el->options['alias'] || in_array($el->type, array('button', 'string')))
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
			if(!in_array($el->type, array('input', 'checkbox', 'radio', 'select', 'textarea')) || !$el->options['validate'])
				continue;
			
			$data[$el->options['name']] = util::cleanWhitespaces($data[$el->options['name']]);
			if(!$el->options['empty'] && !$data[$el->options['name']] && $el->options['validate'][0]!='bool'){
				$return = 'notempty';
				break;
			}
			if(($el->options['validate'][0]=='bool' || $el->options['empty']) && !$data[$el->options['name']])
				$v = true;
			else
				$v = validator::call($el->options['validate'], $data[$el->options['name']]);
			if($v!==true){
				$return = $v;
				break;
			}
		}
		return $return;
	}
	public function addElement($el){
		$this->collector->addElement($el);
		array_push($this->elements, $el);
		return $el;
	}
	public function allowHTML(){
		foreach($this->elements as $key => $el){
			if(is_array($el->options['validate']) && $el->options['validate'][0]=='specialchars')
				$this->elements[$key]->options['validate'] = null;
		}
	}
}
?>