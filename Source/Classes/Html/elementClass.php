<?php
/* ELEMENT CLASS */
abstract class Element {
	
	public $html = '',
		$type = null,
		$options = array(
			/*Keys:
				:caption
				:validate
				:alias (no db field (for username etc.))
				:readOnly (only read from db)
				:empty (input value could be empty too?)
				:detail (dont show in view->all if detail is true)
				:default (for checkbox, defaultvalue)
				-> htmlAttributes
					name, value, type, id etc.
			*/
		);
	
	private static $uid = 0;
	protected static $formElements = array('input', 'checkbox', 'radio', 'select', 'textarea');
	//protected static $skipAttributes = array('caption', 'alias', 'nobreak', 'readOnly', 'events', 'default', 'validate', 'jsvalidate', 'length', 'empty', 'detail', 'elements');
	
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
	}
	
	public function format(){
		return $this->html;
	}
	
	public static function skipable($key){
		return Util::startsWith($key, ':');
	}
	
	public static function implode($a, $options = array(
		/*'skip*' => false,*/
	)){
		if($options && !is_array($options))
			$options = array($options);
		
		if(!is_array($a))
			return '';
		
		foreach($a as $key => $val)
			if(!in_array('skip'.ucfirst($key), $options) && !self::skipable($key))
				$s[] = $key.'="'.$val.'"';
		
		return is_array($s) ? implode(' ', $s) : '';
	}
	
	public function setValue($v){
		$this->options['value'] = $v;
	}
	
	public function getEvents($helper){
		if(!is_array($this->options[':events']))
			return;
		
		$events = array();
		foreach($this->options[':events'] as $key => $ev){
			$event = !db::numeric($key) && $key!==0 ? $ev : 'start';
			$events[] = "$('".$this->options['id']."').addEvent('".(!db::numeric($key) && $key!==0 ? $key : $ev)."', ".(strpos($event, '.') ? $event : $helper.".".$event).".bindWithEvent(".$helper.", $('".$this->options['id']."')));";
		}
		return implode($events);
	}
}
/* ELEMENTS ClASS */
class Elements extends Element {
	
	private $elements = array();
	
	public function __construct(){
		$this->elements = func_get_args();
		if(is_array($this->elements[0]))
			$options = array_shift($this->elements);
		
		parent::__construct($options);
	}
	
	public function addElement($el){
		array_push($this->elements, $el);
		return $el;
	}
	
	public function format($tpl = null, $vars = array()){
		$els = array();
		foreach($this->elements as $el)
			if(!in_array($el->options['type'], array('field')))
				$els[$el->options['name']] = $el->format();
		
		if($tpl)
			$this->html = Template::map('Element', $tpl)->assign($els)->assign($vars)->parse(true);
		else
			$this->html = implode($els);
		
		return $this->html;
	}
}

/* INPUT CLASS */
class Input extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'input');
	}
	
	public function format(){
		if(!$this->options['type'])
			$this->options['type'] = 'text';
		
		if($this->options['type']=='hidden')
			$this->options[':nobreak'] = true;
		
		$this->html = ($this->options['type']!='hidden' && $this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span>'.(!$this->options[':nobreak'] ? '<br/>' : '') : '').'
				<input '.self::implode($this->options).'/>'.(!$this->options[':nobreak'] ? '<br/>' : '');
		
		return $this->html;
	}
}

/* BUTTON CLASS */
class Button extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'button');
	}
	
	public function format(){
		$this->html = '<button '.self::implode($this->options).'>'.$this->options[':caption'].'</button>';
		return $this->html;
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
		$els[] = array();
		foreach($this->options[':elements'] as $val)
			$els[] = '<label><input value="'.$val['value'].'" '.self::implode($this->options, array('skipValue', 'skipId')).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$val[':caption'].'</label>';
		
		if($this->options[':caption'])
			$this->html = '<span class="b">'.$this->options[':caption'].'</span><br/>';
		
		$this->html .= '<div id="'.$this->options['id'].'">'.implode($els).'</div>';
		
		return $this->html;
	}
}

/* SELECT CLASS */
class Select extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'select');
	}
	
	public function format(){
		$els[] = array();
		foreach($this->options[':elements'] as $val)
			$els[] = '<option value="'.$val['value'].'" '.self::implode($this->options, array('skipValue', 'skipId', 'skipName')).($val['value']==$this->options['value'] ? ' selected="selected"' : '').'> '.$val[':caption'].'</option>';
		
		if($this->options[':caption'])
			$this->html = '<span class="b">'.$this->options[':caption'].'</span><br/>';
		
		$this->html .= '<div><select '.self::implode($this->options, 'skipValue').'>'.implode($els).'</select></div>';
		
		return $this->html;
	}
}

/* CHECKBOX CLASS */
class Checkbox extends Element {
	
	public function __construct($options){
		$options['type'] = 'checkbox';
		parent::__construct($options, 'checkbox');
	}
	
	public function format(){
		$this->html = '<label><input value="'.$this->options[':default'].'" '.self::implode($this->options, 'skipValue').' '.($this->options[':default']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$this->options[':caption'].'</label>';
		return $this->html;
	}
}

/* TEXTAREA CLASS */
class Textarea extends Element {
	
	public function __construct($options){
		parent::__construct($options, 'textarea');
	}
	
	public function format(){
		$this->html = ($this->options[':caption'] ? '<span class="b">'.$this->options[':caption'].'</span>'.(!$this->options[':nobreak'] ? '<br/>' : '') : '').'
				<textarea '.self::implode($this->options, 'skipValue').' rows="0" cols="0">'.$this->options['value'].'</textarea><br/>';
		return $this->html;
	}
}
?>