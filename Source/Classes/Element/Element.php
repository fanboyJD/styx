<?php
/*
 * Styx::Element - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Various classes for use in forms to automate data input and processing
 *
 */

class Element extends Runner {
	
	public $name = null,
		$type = null,
		$options = array(
			/*Keys:
				:caption
				:alias - no db field (for username etc.)
				:readOnly - it is not possible to change this element's value in the db
				:default - for checkbox, defaultvalue
				:validate
				:length
				:empty - input value can be empty
				:elements
				:unknown - name/id won't be set automatically when empty
				:tag - type/name given by options
				:standalone - for elements without template; if element gets closed inside the tag (like <img />)
				:preset - stores the initial value for the validator
				:realName - only internally; used in OptionList
				:add - For Checkbox, Input, Radio, Select and Textarea - additional caption
				:template - For use of a custom template
				:custom.* - Reserved for any custom value (to pass to a template for example)
			*/
		);
	
	protected static $formElements = array('input', 'checkbox', 'radio', 'select', 'textarea', 'richtext', 'optionlist');
	
	public function __construct($options, $name = null, $type = null){
		static $uid = 0;
		$type = strtolower($type);
		
		foreach(array(
			'name', 'class', 'value', 'type', 'id',
			':caption', ':alias', ':readOnly', ':default', ':validate', ':length', ':empty',
			':elements', ':unknown', ':tag', ':standalone', ':realName', ':add', ':template',
		) as $v)
			if(empty($options[$v]))
				$options[$v] = null;
		
		if($options[':tag']){
			$this->name = $this->type = $options[':tag'];
		}else{
			$this->name = $name;
			$this->type = $type ? $type : 'element';
		}
		
		Hash::splat($options[':validate']);
		
		if(!$options['id'] && $options['name'])
			$options['id'] = preg_replace('/\W/', '_', $options['name'].'_'.($uid++));
		elseif($options['id'] && !$options['name'])
			$options['name'] = $options['id'];
		elseif(!$options[':unknown'])
			$options['name'] = $options['id'] = $this->type.'_'.($uid++);
		
		if($options['class']){
			if(!is_array($options['class']))
				$options['class'] = explode(' ', $options['class']);
		}else{
			$options['class'] = array();
		}
		
		$options[':preset'] = $options['value'];
		
		$this->options = $options;
	}
	
	public function format($pass = null){
		if(!$pass) $pass = array('attributes' => $this->implode());
		
		$out = Template::map('Element', pick($this->options[':template'], $this->name))->bind($this)->assign($this->options)->assign($pass)->parse(true);
		
		if(!is_array($out)) return $out;
		
		return '<'.$this->type.$pass['attributes'].($this->options[':standalone'] ? ' />' : '>'.$this->options[':caption'].'</'.$this->type.'>');
	}
	
	public static function skipable($key){
		return String::starts($key, ':');
	}
	
	public function setValue($v){
		if($this->options[':readOnly'])
			return;
		
		$this->options['value'] = $v;
	}
	
	public function getValue(){
		if($this->options[':length'][1])
			$this->options['value'] = substr($this->options['value'], 0, $this->options[':length'][1]);
		
		return $this->options['value'];
	}
	
	public function prepareData(){
		$val = $this->getValue();
		
		if(!empty($this->options[':validate'][0]))
			$val = Data::call($val, $this->options[':validate']);
		
		return $val;
	}
	
	/**
	 * @return Element
	 */
	public function addClass($class){
		if(!$this->hasClass($class)) array_push($this->options['class'], $class);
		
		return $this;
	}
	
	/**
	 * @return Element
	 */
	public function removeClass($class){
		Hash::remove($this->options['class'], $class);
		
		return $this;
	}
	
	public function hasClass($class){
		return in_array($class, $this->options['class']);
	}
	
	/**
	 * @return Element
	 */
	public function set($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				$this->set($k, $val);
			
			return;
		}
		
		if(empty($this->options[$key]) || $this->options[$key]!=$value){
			$this->options[$key] = $value;
			if(!$value) unset($this->options[$key]);
		}
		
		return $this;
	}
	
	public function get($key, $value = null){
		if($value && empty($this->options[$key]))
			$this->set($key, $value);
		
		return $this->options[$key];
	}
	
	public function implode($options = array(
		/*'skip*' => false,*/
	)){
		$a = $this->options;
		
		if($options && !is_array($options))
			$options = array($options);
		
		if(!is_array($a))
			return '';
		
		if(is_array($a['class']) && count($a['class']))
			$a['class'] = implode(' ', $a['class']);
		else
			unset($a['class']);
		
		foreach($a as $key => $val)
			if($val!==false && !in_array('skip'.ucfirst($key), $options) && !self::skipable($key))
				$s[] = $key.'="'.($key=='style' ? str_replace('"', "'", $val) : Data::entities($val)).'"';
		
		return is_array($s) ? ' '.implode(' ', $s) : '';
	}
	
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct(){
		$elements = func_get_args();
		if(is_subclass_of($this, 'Elements')){
			$name = isset($elements[1]) ? $elements[1] : null;
			$type = isset($elements[2]) ? $elements[2] : null;
			$elements = $elements[0];
		}
		
		$options = array();
		
		if(is_array($elements[0])) $options = array_shift($elements);
		
		foreach($elements as $el)
			$this->elements[$el->options['name']] = $el;
		
		parent::__construct($options, $name ? $name : get_class(), $type);
	}
	
	public function format(){
		$els = array();
		foreach($this->elements as $n => $el)
			if(!in_array($el->options['type'], array('field')) && !$el->options[':readOnly'])
				if($format = $el->format()){
					if($el->name=='HiddenInput') $els['form.hidden'][] = $format;
					else $els[$n] = $format;
				}
		
		if(isset($els['form.hidden']) && is_array($els['form.hidden']))
			$els['form.hidden'] = implode($els['form.hidden']);
		
		return $els;
	}
	
	/**
	 * @return Element
	 */
	public function getElement($name){
		return pick($this->elements[$name]);
	}
	
	/**
	 * @return Element
	 */
	public function addElement($el){
		if(!$this->hasElement($el))
			$this->elements[$el->options['name']] = $el;
		
		return $el;
	}
	
	public function removeElement($el){
		Hash::remove($this->elements, $el);
	}
	
	public function hasElement($el){
		return !empty($this->elements[$el->options['name']]);
	}
	
	public function getElements(){
		return $this->elements;
	}
	
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options, $name = null){
		if(empty($options['type']))
			$options['type'] = 'text';
		
		parent::__construct($options, $name ? $name : get_class(), 'input');
	}
	
}

/* HIDDEN CLASS */

class HiddenInput extends Input {
	
	public function __construct($options){
		$options['type'] = 'hidden';
		
		parent::__construct($options, get_class());
	}
	
}

/* BUTTON CLASS */
class Button extends Element {
	
	public function __construct($options){
		parent::__construct($options, get_class(), 'button');
		
		$this->options['type'] = 'submit';
	}
	
}

/* FIELD CLASS */
class Field extends Element {
	
	public function __construct($options){
		parent::__construct($options, get_class(), 'field');
	}
	
	public function format(){
		return '';
	}
	
}

/* TEMPLATE CLASS FOR RADIO AND SELECT */
class Radio extends Element {
	
	public function __construct($options, $name = null, $type = null){
		if(!$name && !$type){
			$options['type'] = $type = 'radio';
			$name = get_class().'.php';
		}
		
		Hash::splat($options[':elements']);
		parent::__construct($options, $name, $type);
		
		// This class returns the preset value if wrong value is set
		if(isset($this->options[':empty']) && !$this->options[':empty'])
			$this->options[':empty'] = true;
		
		if($type=='radio') $this->addClass('radio');
	}
	
	public function addElement($el){
		if(!$this->hasElement($el))
			$this->options[':elements'][] = $el;
		
		return $el;
	}
	
	public function removeElement($el){
		Hash::remove($this->options[':elements'], $el);
	}
	
	public function hasElement($el){
		return in_array($el, $this->options[':elements']);
	}
	
	public function getValue(){
		foreach($this->options[':elements'] as $el)
			if($this->options['value']==$el['value'])
				return $this->options['value'];
		
		return $this->options[':preset'];
	}
	
	public function getSelectedElement(){
		$val = $this->getValue();
		
		foreach($this->options[':elements'] as $el)
			if($el['value']==$val)
				return $el;
		
		return null;
	}
	
}

/* SELECT CLASS */
class Select extends Radio {
	
	public function __construct($options){
		parent::__construct($options, get_class().'.php', 'select');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipValue'),
		));
	}
	
}

/* CHECKBOX CLASS */
class Checkbox extends Element {
	
	public function __construct($options){
		$options['type'] = 'checkbox';
		$options['value'] = 1;
		$options[':validate'] = 'bool';
		if($options[':default']!=1) $options[':default'] = 0;
		
		parent::__construct($options, get_class(), 'checkbox');
		
		$this->addClass('checkbox');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipChecked'),
			'checked' => ($this->options[':default']==$this->options['value'] ? 'checked="checked" ' : ''),
		));
	}
	
	public function setValue($v){
		if($this->options[':readOnly'])
			return;
		
		if($this->options['value']==$v)
			$this->options['checked'] = 1;
		
		$this->options[':default'] = $v;
	}
	
	public function getValue(){
		return $this->options['checked'] ? 1 : 0;
	}
	
}

/* TEXTAREA CLASS */
class Textarea extends Element {
	
	public function __construct($options, $type = null){
		foreach(array('cols', 'rows') as $v)
			if(empty($options[$v])) $options[$v] = 0;
		
		parent::__construct($options, get_class(), $type ? $type : 'textarea');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipValue'),
		));
	}
	
}

/* RICHTEXT CLASS */
class RichText extends Textarea {
	
	public function __construct($options){
		parent::__construct($options, get_class());
		
		$this->addClass('richtext');
	}
	
}

/* JSON OPTIONS CLASS */

class OptionList extends Elements {
	
	public function __construct(){
		parent::__construct(func_get_args(), get_class(), 'optionlist');
		
		if(is_array($this->options[':elements']))
			array_walk($this->options[':elements'], array($this, 'createElement'));
		
		unset($this->options[':elements']);
	}
	
	public function format(){
		return implode(parent::format());
	}
	
	public function setValue($v){
		if($v && !is_array($v)) $v = json_decode($v, true);
		
		if(!is_array($v)) return;
		
		foreach($this->elements as $el){
			if(!$v[$el->options[':realName']]) continue;
			
			$el->setValue($v[$el->options[':realName']]);
		}
	}
	
	public function getValue(){
		$json = array();
		
		foreach($this->elements as $el)
			$json[$el->options[':realName']] = $el->getValue();
		
		return json_encode($json);
	}
	
	/**
	 * @return Element
	 */
	public function addElement($el){
		return $this->createElement($el);
	}
	
	private function createElement($el){
		$el[':realName'] = $el['name'];
		$el['name'] = $this->options['name'].'['.$el['name'].']';
		
		$element = new Checkbox($el);
		if(!$this->hasElement($element))
			$this->elements[$el['name']] = $element;
			
		return $this->elements[$el['name']];
	}
	
}