<?php
/* ELEMENT CLASS */
abstract class Element {
	
	public $type = null,
		$options = array(
			/*Keys:
				:caption
				:alias (no db field (for username etc.))
				:nobreak
				:readOnly (only read from db)
				:events
				:default (for checkbox, defaultvalue)
				:validate
				:jsvalidate
				:length
				:empty (input value could be empty too?)
				:detail (dont show in view->all if detail is true)
				:elements
			*/
		);
	
	private static $uid = 0;
	protected static $formElements = array('input', 'checkbox', 'radio', 'select', 'textarea', 'richtext');
	
	public function __construct($options, $type = 'element'){
		$this->options = $options;
		$this->type = $type ? $type : 'element';
		
		if($this->options[':validate'] && !is_array($this->options[':validate']))
			$this->options['validate'] = array($this->options['validate']);
		
		if(!$this->options['id'])
			$this->options['id'] = $this->options['name'].'_'.(self::$uid++);
		elseif(!$this->options['name'])
			$this->options['name'] = $this->options['id'];
		else
			$this->options['name'] = $this->options['id'] = $this->type.'_'.(self::$uid++);
		
		if($this->options['class']){
			if(!is_array($this->options['class']))
				$this->options['class'] = explode(' ', $this->options['class']);
		}else{
			$this->options['class'] = array();
		}
	}
	
	public function format(){
		return '';
	}
	
	public static function skipable($key){
		return Util::startsWith($key, ':');
	}
		
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
		$this->options['value'] = $v;
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
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	protected $elements = array();
	
	public function __construct(){
		$this->elements = func_get_args();
		if(is_subclass_of($this, 'Elements'))
			$this->elements = $this->elements[0];
		
		if(is_array($this->elements[0]))
			$options = array_shift($this->elements);
		
		parent::__construct($options);
	}
	
	public function addElement($el){
		array_push($this->elements, $el);
		return $el;
	}
	
	public function format($tpl = null){
		$els = array();
		foreach($this->elements as $el)
			if(!in_array($el->options['type'], array('field')))
				$els[$el->options['name']] = $el->format();
		
		if($tpl)
			$out = Template::map('Element', $tpl)->assign($els)->parse(true);
		else
			$out = implode($els);
		
		return $out;
	}
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options){
		if(!$options['type'])
			$options['type'] = 'text';
		
		parent::__construct($options, 'input');
	}
	
	public function format(){
		return ($this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span>'.(!$this->options[':nobreak'] ? '<br/>' : '') : '').'
			<input'.$this->implode().'/>'.(!$this->options[':nobreak'] ? '<br/>' : '');
	}
}

/* HIDDEN CLASS */

class HiddenInput extends Input {
	
	public function __construct($options){
		$options['type'] = 'hidden';
		
		parent::__construct($options);
	}
	
	public function format(){
		return '<input'.$this->implode().'/>';
	}
}

/* BUTTON CLASS */
class Button extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'button');
	}
	
	public function format(){
		return '<button'.$this->implode().'>'.$this->options[':caption'].'</button>';
	}
}

/* FIELD CLASS */
class Field extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'field');
	}
	
	public function format(){
		return '';
	}
}

/* RADIO CLASS */
class Radio extends Element {
	
	public function __construct($options){
		$options['type'] = 'radio';
		
		parent::__construct($options, 'radio');
	}
	
	public function format(){
		foreach($this->options[':elements'] as $val)
			$els[] = '<label><input value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId')).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$val[':caption'].'</label>';
		
		return ($this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span><br/>' : '').'
			<div id="'.$this->options['id'].'">'.implode($els).'</div>';
	}
}

/* SELECT CLASS */
class Select extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'select');
	}
	
	public function format(){
		foreach($this->options[':elements'] as $val)
			$els[] = '<option value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId', 'skipName')).($val['value']==$this->options['value'] ? ' selected="selected"' : '').'>'.$val[':caption'].'</option>';
		
		return ($this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span><br/>' : '').'
			<div><select'.$this->implode('skipValue').'>'.implode($els).'</select></div>';
	}
}

/* CHECKBOX CLASS */
class Checkbox extends Element {
	
	public function __construct($options){
		$options['type'] = 'checkbox';
		
		parent::__construct($options, 'checkbox');
	}
	
	public function format(){
		return '<label><input value="'.$this->options[':default'].'"'.$this->implode('skipValue').' '.($this->options[':default']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$this->options[':caption'].'</label>';
	}
}

/* TEXTAREA CLASS */
class Textarea extends Element {
	
	public function __construct($options, $type = null){
		$options['cols'] = 0;
		$options['rows'] = 0;
		parent::__construct($options, $type ? $type : 'textarea');
	}
	
	public function format(){
		return ($this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span>'.(!$this->options[':nobreak'] ? '<br/>' : '') : '').'
				<textarea'.$this->implode('skipValue').'>'.$this->options['value'].'</textarea><br/>';
	}
}

/* RICHTEXT CLASS */
class RichText extends Textarea {
	
	public function __construct($options){
		parent::__construct($options, 'richtext');
		
		$this->addClass('richtext');
	}
	
}
?>