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
			/*
			 * :tag - type/name given by options
			 * :caption
			 * :add - Additional caption
			 * :validate
			 * :elements
			 * :default - for checkbox, defaultvalue
			 * :template - For use of a custom template
			 * :custom.* - Reserved for any custom value (to pass to a template for example)
			*/
		);
	
	public function __construct($options = array()){
		if(!empty($options[':tag'])){
			$this->name = $this->type = $options[':tag'];
		}else{
			$this->name = pick(substr(get_class($this), 0, -7), 'Element');
			$this->type = strtolower($this->name);
			if($this->type=='element') $this->type = 'div';
		}
		
		$hasId = !empty($options['id']);
		$hasName = !empty($options['name']);
		
		static $uid = 0;
		if(!$hasId && $hasName) $options['id'] = preg_replace('/(?:[^A-z0-9]|_|\^)+/', '_', $options['name'].'_'.($uid++));
		elseif($hasId && !$hasName) $options['name'] = $options['id'];
		
		if(!empty($options['class']) && !is_array($options['class'])) $options['class'] = explode(' ', $options['class']);
		elseif(empty($options['class'])) $options['class'] = array();
		
		if(empty($options['value'])) $options['value'] = null;
		
		$this->options = $options;
	}
	
	public function format($pass = null){
		if(!$pass) $pass = array('attributes' => $this->implode());
		
		$out = Template::map('Element', !empty($this->options[':template']) ? $this->options[':template'] : $this->name)->bind($this)->assign($this->options)->assign($pass)->parse(true);
		
		if(!is_array($out)) return $out;
		
		if(in_array($this->type, array('area', 'br', 'img', 'input', 'hr', 'wbr', 'param', 'link')))
			return '<'.$this->type.$pass['attributes'].' />';
		
		return '<'.$this->type.$pass['attributes'].'>'.$this->get(':caption').'</'.$this->type.'>';
	}
	
	public function setValue($v){
		$this->options['value'] = $v;
	}
	
	public function getValue(){
		if(!empty($this->options[':validate']))
			return Data::call($this->options['value'], $this->options[':validate']);
		
		return $this->options['value'];
	}
	
	public function validate(){
		if(!empty($this->options[':validate']))
			if(($v = Validator::call($this->options['value'], $this->options[':validate']))!==true)
				throw new ValidatorException($v, !empty($this->options[':caption']) ? $this->options[':caption'] : (!empty($this->options['name']) ? $this->options['name'] : null));
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
			if($value) $this->options[$key] = $value;
			else unset($this->options[$key]);
		
		return $this;
	}
	
	public function get($key, $value = null){
		if($value && empty($this->options[$key]))
			$this->set($key, $value);
		
		return !empty($this->options[$key]) ? $this->options[$key] : null;
	}
	
	protected function skipable($key){
		return $key[0]==':';
	}
	
	public function implode($skip = array(
		/*'skip*' => false,*/
	), $options = null){
		$a = $this->options;
		if(is_array($options)) Hash::extend($a, $options);
		Hash::splat($skip);
		
		if(!count($a)) return '';
		
		if(count($a['class'])) $a['class'] = implode(' ', $a['class']);
		else unset($a['class']);
		
		$s = array();
		foreach($a as $key => $val)
			if(($val || $val===0) && !$this->skipable($key) && !in_array('skip'.ucfirst($key), $skip))
				$s[] = $key.'="'.($key=='style' ? str_replace('"', "'", $val) : Data::sanitize($val)).'"';
		
		return count($s) ? ' '.implode(' ', $s) : '';
	}
	
}

/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct($options = array()){
		parent::__construct($options);
		
		if(!empty($this->options[':elements'])) $this->addElements($this->options[':elements']);
		unset($this->options[':elements']);
	}
	
	public function format(){
		$els = array();
		$els['form.hidden'] = array();
		
		foreach($this->elements as $name => $el)
			if($format = $el->format()){
				if($el instanceof HiddenElement) $els['form.hidden'][] = $format;
				else $els[$name] = $format;
			}
		
		$els['form.hidden'] = count($els['form.hidden']) ? implode($els['form.hidden']) : null;
		
		return $els;
	}
	
	/**
	 * Sets value for elements inside the container.
	 *
	 * @param array $data
	 */
	public function setValue($data){
		if(!Hash::length($data)) return;
		
		foreach($data as $name => $el)
			if(!empty($this->elements[$name]))
				$this->elements[$name]->setValue($el);
	}
	
	public function getValue($name){
		return !empty($this->elements[$name]) ? $this->elements[$name]->getValue() : false;
	}
	
	public function validate(){
		foreach($this->elements as $el)
			$el->validate();
	}
	
	/**
	 * @return Element
	 */
	public function getElement($name){
		return !empty($this->elements[$name]) ? $this->elements[$name] : false;
	}
	
	public function getElements(){
		return $this->elements;
	}
	
	public function hasElement($el){
		return is_object($el) ? !empty($this->elements[$el->options['name']]) : !empty($this->elements[$el]);
	}
	
	/**
	 * @return Element
	 */
	public function addElement($el){
		if(!$this->hasElement($el)) $this->elements[$el->options['name']] = $el;
		
		return $el;
	}
	
	public function addElements(){
		foreach(Hash::args(func_get_args()) as $el)
			if(!$this->hasElement($el))
				$this->elements[$el->options['name']] = $el;
	}
	
	public function removeElement($el){
		if(is_string($el)) $el = $this->getElement($el);
		
		Hash::remove($this->elements, $el);
		
		return $this;
	}
	
}

class FormElement extends Elements {
	
	public function __construct($options = array()){
		if(empty($options['method']))
			$options['method'] = 'post';
		
		parent::__construct($options);
	}
	
	public function format(){
		if($this->hasInstance('UploadElement')) // Sets the enctype if needed and if it hasn't been set manually yet
			$this->get('enctype', 'multipart/form-data');
		
		$out = array('form.top' => '<form'.$this->implode('skipName').'><div>');
		Hash::extend($out, parent::format());
		$out['form.bottom'] = '</div></form>';
		
		static $prefix = false;
		if($prefix===false) $prefix = pick(Core::retrieve('elements.prefix'));
		return $prefix ? array($prefix => $out) : $out;
	}
	
	public function hasInstance($instance){
		foreach($this->elements as $el)
			if($el instanceof $instance)
				return true;
	}
	
}

class InputElement extends Element {
	
	public function __construct($options){
		if(empty($options['type'])) $options['type'] = 'text';
		
		parent::__construct($options, 'input');
	}
	
}

class HiddenElement extends InputElement {
	
	public function __construct($options){
		$options['type'] = 'hidden';
		
		parent::__construct($options);
	}
	
	public function format($pass = null){
		if(empty($this->options[':template'])) $this->options[':template'] = 'Hidden';
		
		return parent::format($pass);
	}
	
}

class UploadElement extends InputElement {
	
	public function __construct($options){
		$options['type'] = 'file';
		
		parent::__construct($options);
	}
	
	public function format($pass = null){
		if(empty($this->options[':template'])) $this->options[':template'] = 'Input';
		
		return parent::format($pass);
	}
	
}

class ButtonElement extends Element {
	
	public function __construct($options){
		$options['type'] = 'submit';
		
		parent::__construct($options);
	}
	
}

class RadioElement extends Element {
	
	public function __construct($options){
		if(empty($options[':elements'])) $options[':elements'] = array();
		
		parent::__construct($options);
		
		if($this->type=='radio'){
			if(empty($this->options['type'])) $this->options['type'] = 'radio';
			$this->addClass('radio');
		}
		
		if(empty($this->options[':template'])) $this->options[':template'] = $this->name.'.php';
		$this->options[':default'] = $this->options['value'];
	}
	
	public function addElement($el){
		if(!$this->hasElement($el)) $this->options[':elements'][] = $el;
		
		return $el;
	}
	
	public function removeElement($el){
		Hash::remove($this->options[':elements'], $el);
	}
	
	public function hasElement($el){
		return in_array($el, $this->options[':elements']);
	}
	
	public function setValue($v){
		foreach($this->options[':elements'] as $el)
			if($v==$el['value']){
				$this->options['value'] = $el['value'];
				return;
			}
		
		$this->options['value'] = $this->options[':default'];
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
class SelectElement extends RadioElement {
	
	public function __construct($options){
		parent::__construct($options);
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipValue'),
		));
	}
	
}

/* CHECKBOX CLASS */
class CheckboxElement extends Element {
	
	public function __construct($options){
		$options['type'] = 'checkbox';
		$options['value'] = 1;
		$options[':validate'] = 'bool';
		if(empty($options[':default']) || $options[':default']!=1) $options[':default'] = 0;
		
		parent::__construct($options, 'checkbox');
		
		$this->addClass('checkbox');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipChecked'),
			'checked' => ($this->options[':default']==$this->options['value'] ? ' checked="checked"' : ''),
		));
	}
	
	public function setValue($v){
		$this->options['checked'] = $this->options['value']==$v;
		$this->options[':default'] = !!$v;
	}
	
	public function getValue(){
		return !empty($this->options['checked']);
	}
	
}

class TextareaElement extends Element {
	
	public function __construct($options){
		foreach(array('cols', 'rows') as $v)
			if(empty($options[$v])) $options[$v] = 0;
		
		parent::__construct($options);
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipValue'),
		));
	}
	
}

class RichTextElement extends TextareaElement {
	
	public function __construct($options){
		parent::__construct($options);
		
		$this->addClass('richtext');
	}
	
}