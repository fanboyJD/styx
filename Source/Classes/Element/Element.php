<?php
/* ELEMENT CLASS */
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
				:unknown - name/id does not get set automatically when not given)
				:tag - type/name given by options
				:standalone - for elements without template; if element gets closed inside the tag (like <img />)
				:preset - stores the initial value for the validator
				:realName - only internally; used in OptionList
				:add - For Checkbox, Input, Radio, Select and Textarea - additional caption
				:template - For use of a custom template
				:custom.* - Reserved for any custom value (to pass to a template for example)
			*/
		);
	
	protected static $uid = 0,
		$formElements = array('input', 'checkbox', 'radio', 'select', 'textarea', 'richtext', 'optionlist');
	
	public function __construct($options, $name = null, $type = null){
		$type = strtolower($type);
		
		if($options[':tag']){
			$this->name = $this->type = $options[':tag'];
		}else{
			$this->name = $name;
			$this->type = $type ? $type : 'element';
		}
		
		Hash::splat($options[':validate']);
		
		if(!$options['id'] && $options['name'])
			$options['id'] = preg_replace('/\W/', '_', $options['name'].'_'.(self::$uid++));
		elseif($options['id'] && !$options['name'])
			$options['name'] = $options['id'];
		elseif(!$options[':unknown'])
			$options['name'] = $options['id'] = $this->type.'_'.(self::$uid++);
		
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
		
		if($out) return $out;
		
		return '<'.$this->type.$pass['attributes'].($this->options[':standalone'] ? ' />' : '>'.$this->options[':caption'].'</'.$this->type.'>');
	}
	
	public static function skipable($key){
		return startsWith($key, ':');
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
		if(!$this->options[$key] || $this->options[$key]!=$value){
			$this->options[$key] = $value;
			if(!$value) unset($this->options[$key]);
		}
		
		return $this;
	}
	
	public function get($key, $value = null){
		if($value && !$this->options[$key])
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
		
		if(is_array($a['class']) && sizeof($a['class']))
			$a['class'] = implode(' ', $a['class']);
		else
			unset($a['class']);
		
		foreach($a as $key => $val)
			if($val!==false && !in_array('skip'.ucfirst($key), $options) && !self::skipable($key))
				$s[] = $key.'="'.($key=='style' ? str_replace('"', "'", $val) : htmlspecialchars($val, ENT_COMPAT, 'UTF-8', false)).'"';
		
		return is_array($s) ? ' '.implode(' ', $s) : '';
	}
	
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct(){
		$elements = func_get_args();
		if(is_subclass_of($this, 'Elements')){
			$name = $elements[1];
			$type = $elements[2];
			$elements = $elements[0];
		}
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
					if($el->name=='HiddenInput')
						$els['form.hidden'][] = $format;
					else
						$els[$n] = $format;
				}
		
		if(is_array($els['form.hidden']))
			$els['form.hidden'] = implode($els['form.hidden']);
		
		return $els;
	}
	
	/**
	 * @return Element
	 */
	public function getElement($name){
		return pick($this->elements[$name], null);
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
		return !!$this->elements[$el->options['name']];
	}
	
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options, $name = null){
		if(!$options['type'])
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
			if(!$options[$v]) $options[$v] = 0;
		
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
			foreach($this->options[':elements'] as $el)
				$this->createElement($el);
		
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