<?php
/* ELEMENT CLASS */
class Element extends Runner {
	
	public $name = null,
		$type = null,
		$options = array(
			/*Keys:
				:caption
				:alias (no db field (for username etc.))
				:readOnly (only read from db)
				:events
				:default (for checkbox, defaultvalue)
				:validate
				:jsvalidate
				:length
				:empty (input value could be empty too?)
				:detail (dont show in view->all if detail is true)
				:elements
				:unknown (name/id does not get set automatically when not given)
				:tag (type/name given by options)
				:standalone (for elements without template; if element gets closed inside the tag (like <img />)
			*/
		);
	
	protected static $uid = 0,
		$formElements = array('input', 'checkbox', 'radio', 'select', 'textarea', 'richtext');
	
	public function __construct($options, $name = null, $type = null){
		if($options[':tag']){
			$this->name = $this->type = $options[':tag'];
		}else{
			$this->name = $name;
			$this->type = $type ? $type : 'element';
		}
		
		splat($options[':validate']);
		
		if(!$options['id'] && $options['name'])
			$options['id'] = $options['name'].'_'.(self::$uid++);
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
		
		$this->options = $options;
	}
	
	public function format($pass = null){
		if(!$pass) $pass = array('attributes' => $this->implode());
		
		$out = Template::map('Element', $this->name)->object($this)->assign($this->options)->assign($pass)->parse(true);
		
		if($out) return $out;
		
		return '<'.$this->type.$pass['attributes'].($this->options[':standalone'] ? ' />' : '>'.$this->options[':caption'].'</'.$this->type.'>');
	}
	
	public static function skipable($key){
		return startsWith($key, ':');
	}
	
	//have a look at this one
	public function getEvents($helper){
		if(!is_array($this->options[':events']))
			return;
		
		$events = array();
		foreach($this->options[':events'] as $key => $ev){
			$event = !Data::id($key) && $key!==0 ? $ev : 'start';
			$events[] = "$('".$this->options['id']."').addEvent('".(!Data::id($key) && $key!==0 ? $key : $ev)."', ".(strpos($event, '.') ? $event : $helper.".".$event).".bindWithEvent(".$helper.", $('".$this->options['id']."')));";
		}
		
		return implode($events);
	}
	
	public function setValue($v){
		return $this->options['value'] = $v;
	}
	
	public function getValue(){
		return $this->options['value'];
	}
	
	public function addClass($class){
		if(!$this->hasClass($class)) array_push($this->options['class'], $class);
	}
	
	public function removeClass($class){
		array_remove($this->options['class'], $class);
	}
	
	public function hasClass($class){
		return in_array($class, $this->options['class']);
	}
	
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
				$s[] = $key.'="'.$val.'"';
		
		return is_array($s) ? ' '.implode(' ', $s) : '';
	}
	
	public function prepareData(){
		if($this->options[':length'][1])
			$this->options['value'] = substr($this->options['value'], 0, $this->options[':length'][1]);
		
		return array($this->options['value'], $this->options[':validate']);
	}
	
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct(){
		$elements = func_get_args();
		if(is_subclass_of($this, 'Elements')){
			$name = $elements[1];
			$elements = $elements[0];
		}
		if(is_array($elements[0]))
			$options = array_shift($elements);
		
		foreach($elements as $el)
			$this->elements[$el->options['name']] = $el;
		
		parent::__construct($options, $name ? $name : get_class());
	}
	
	public function format(){
		$els = array();
		foreach($this->elements as $n => $el)
			if(!in_array($el->options['type'], array('field')) && !$el->options[':readOnly']){
				$format = $el->format();
				if($format) $els[$n] = $format;
			}
		
		return $els;
	}
	
	public function addElement($el){
		if(!$this->hasElement($el))
			$this->elements[$el->options['name']] = $el;
		
		return $el;
	}
	
	public function removeElement($el){
		array_remove($this->elements, $el);
	}
	
	public function hasElement($el){
		return !!$this->elements[$el->options['name']];
	}
	
	public function getElement($name){
		return $this->elements[$name];
	}
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options){
		if(!$options['type'])
			$options['type'] = 'text';
		
		parent::__construct($options, get_class(), 'input');
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
class TemplateRadioSelect extends Element {
	
	public function addElement($el){
		if(!$this->hasElement($el))
			$this->options[':elements'][] = $el;
		
		return $el;
	}
	
	public function removeElement($el){
		array_remove($this->options[':elements'], $el);
	}
	
	public function hasElement($el){
		return in_array($el, $this->options[':elements']);
	}
	
}

/* RADIO CLASS */
class Radio extends TemplateRadioSelect {
	
	public function __construct($options){
		$options['type'] = 'radio';
		
		parent::__construct($options, get_class().'.php', 'radio');
	}
	
}

/* SELECT CLASS */
class Select extends TemplateRadioSelect {
	
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
		
		parent::__construct($options, get_class(), 'checkbox');
	}
	
	public function format(){
		return parent::format(array(
			'attributes' => $this->implode('skipValue'),
			'checked' => ($this->options[':default']===$this->options['value'] ? 'checked="checked" ' : ''),
		));
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
		parent::__construct($options, get_class(), 'richtext');
		
		$this->addClass('richtext');
	}
	
}