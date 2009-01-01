<?php
/*
 * Styx::Element - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Various classes for use in forms to automate data input and processing
 *
 */

class Element extends Runner {
	
	public $type = null;
	protected $name = null,
		$options = array(
			/*Keys:
				:caption
				:alias - no db field (for username etc.)
				:default - for checkbox, defaultvalue
				:validate
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
	
	public function __construct($options, $type = null){
		static $uid = 0;
		$type = String::toLower($type);
		
		if(!empty($options[':tag'])){
			$this->name = $this->type = $options[':tag'];
		}else{
			if(!$this->name) $this->name = get_class($this);
			$this->type = $type ? $type : 'element';
		}
		
		$hasId = !empty($options['id']);
		$hasName = !empty($options['name']);
		
		if(!$hasId && $hasName)
			$options['id'] = preg_replace('/\W/', '_', $options['name'].'_'.($uid++));
		elseif($hasId && !$hasName)
			$options['name'] = $options['id'];
		elseif(empty($options[':unknown']))
			$options['name'] = $options['id'] = $this->type.'_'.($uid++);
		
		if(!empty($options['class'])){
			if(!is_array($options['class']))
				$options['class'] = explode(' ', $options['class']);
		}else{
			$options['class'] = array();
		}
		
		$options[':preset'] = !empty($options['value']) ? $options['value'] : null;
		
		$this->options = $options;
	}
	
	public function format($pass = null){
		if(!$pass) $pass = array('attributes' => $this->implode());
		
		$out = Template::map('Element', !empty($this->options[':template']) ? $this->options[':template'] : $this->name)->bind($this)->assign($this->options)->assign($pass)->parse(true);
		
		if(!is_array($out)) return $out;
		
		return '<'.$this->type.$pass['attributes'].(!empty($this->options[':standalone']) ? ' />' : '>'.$this->get(':caption').'</'.$this->type.'>');
	}
	
	public static function skipable($key){
		return String::starts($key, ':');
	}
	
	public function setValue($v){
		$this->options['value'] = $v;
	}
	
	public function getValue(){
		return !empty($this->options['value']) ? $this->options['value'] : null;
	}
	
	public function prepare(){
		$val = $this->getValue();
		
		if(!empty($this->options[':validate']))
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
	public function set($array, $value = null){
		if(!is_array($array))
			$array = array($array => $value);
		
		foreach($array as $key => $value)
			if(empty($this->options[$key]) || $this->options[$key]!=$value){
				if($value) $this->options[$key] = $value;
				else unset($this->options[$key]);
			}
		
		return Hash::length($array)==1 ? $value : $array;
	}
	
	public function get($key, $value = null){
		if($value && empty($this->options[$key]))
			$this->set($key, $value);
		
		return !empty($this->options[$key]) ? $this->options[$key] : null;
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
			if(($val || $val===0) && !in_array('skip'.String::ucfirst($key), $options) && !self::skipable($key))
				$s[] = $key.'="'.($key=='style' ? String::replace('"', "'", $val) : Data::sanitize($val)).'"';
		
		return is_array($s) ? ' '.implode(' ', $s) : '';
	}
	
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct(){
		$type = null;
		$elements = func_get_args();
		
		if(is_subclass_of($this, 'Elements')){
			$type = isset($elements[1]) ? $elements[1] : null;
			$elements = $elements[0];
		}
		
		$options = array();
		
		if(isset($elements[0]) && is_array($elements[0])) $options = array_shift($elements);
		
		$this->addElements($elements);
		
		parent::__construct($options, $type);
	}
	
	public function format(){
		$els = array();
		foreach($this->elements as $n => $el)
			if(!in_array($el->type, array('field')))
				if($format = $el->format()){
					if($el->name=='HiddenInput') $els['form.hidden'][] = $format;
					else $els[$n] = $format;
				}
		
		if(!empty($els['form.hidden']) && is_array($els['form.hidden']))
			$els['form.hidden'] = implode($els['form.hidden']);
		
		return $els;
	}
	
	/**
	 * @return Element
	 */
	public function getElement($name){
		return !empty($this->elements[$name]) ? $this->elements[$name] : null;
	}

	/**
	 * @return Element
	 */
	public function addElement($el){
		if(!$this->hasElement($el))
			$this->elements[$el->options['name']] = $el;
		
		return $el;
	}
	
	public function addElements(){
		foreach(Hash::args(func_get_args()) as $el)
			if(!$this->hasElement($el))
				$this->elements[$el->options['name']] = $el;
	}
	
	public function removeElement($el){
		Hash::remove($this->elements, $el);
	}
	
	public function hasElement($el){
		return !empty($this->elements[$el->options['name']]);
	}
	
	public function hasInstanceOf($instance){
		foreach($this->elements as $el)
			if($el instanceof $instance)
				return true;
	}
	
	public function getElements(){
		return $this->elements;
	}
	
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options){
		if(empty($options['type']))
			$options['type'] = 'text';
		
		parent::__construct($options, 'input');
	}
	
}

/* HIDDEN CLASS */

class HiddenInput extends Input {
	
	public function __construct($options){
		$options['type'] = 'hidden';
		
		parent::__construct($options);
	}
	
}

class UploadInput extends Input {
	
	public function __construct($options){
		$options['type'] = 'file';
		
		parent::__construct($options);
	}
	
	public function format($pass = null){
		if(empty($this->options[':template']))
			$this->options[':template'] = 'Input';
		
		return parent::format($pass);
	}
	
}

/* BUTTON CLASS */
class Button extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'button');
		
		$this->options['type'] = 'submit';
	}
	
}

/* FIELD CLASS */
class Field extends Element {
	
	public function __construct($options){
		if(is_string($options)) $options = array('name' => $options);
		
		parent::__construct($options, 'field');
	}
	
	public function format(){
		return '';
	}
	
}

/* TEMPLATE CLASS FOR RADIO AND SELECT */
class Radio extends Element {
	
	public function __construct($options, $type = null){
		if(!$type) $options['type'] = $type = 'radio';
		$this->name = get_class($this).'.php';
		
		Hash::splat($options[':elements']);
		parent::__construct($options, $type);
		
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
		parent::__construct($options, 'select');
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
		if(isset($options[':default']) && $options[':default']!=1) $options[':default'] = 0;
		
		parent::__construct($options, 'checkbox');
		
		$this->addClass('checkbox');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipChecked'),
			'checked' => ($this->options[':default']==$this->options['value'] ? 'checked="checked" ' : ''),
		));
	}
	
	public function setValue($v){
		if($this->options['value']==$v)
			$this->options['checked'] = 1;
		
		$this->options[':default'] = $v;
	}
	
	public function getValue(){
		return !empty($this->options['checked']) ? 1 : 0;
	}
	
}

/* TEXTAREA CLASS */
class Textarea extends Element {
	
	public function __construct($options, $type = null){
		foreach(array('cols', 'rows') as $v)
			if(empty($options[$v])) $options[$v] = 0;
		
		parent::__construct($options, $type ? $type : 'textarea');
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
		parent::__construct($options);
		
		$this->addClass('richtext');
	}
	
}

/* JSON OPTIONS CLASS */

class OptionList extends Elements {
	
	public function __construct(){
		parent::__construct(func_get_args(), 'optionlist');
		
		if(!empty($this->options[':elements']) && is_array($this->options[':elements']))
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