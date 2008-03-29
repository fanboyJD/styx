<?php
/* ELEMENT CLASS */
abstract class element {
	public $html,
		$type = null,
		$options = array(
			/*Keys:
				caption
				validate
				skip
				alias (no db field (for username etc.))
				readOnly (only read from db)
				empty (input value could be empty too?)
				detail (dont show in view->all if detail is true)
				default (for checkbox, defaultvalue)
				-> htmlAttributes
					name, value, type, id etc.
			*/
		);
	private static $uniques = 0;
	protected static $skipAttributes = array('skip', 'caption', 'alias', 'nobreak', 'readOnly', 'events', 'default', 'validate', 'jsvalidate', 'length', 'empty', 'detail', 'elements');
	public function __construct($options, $type = 'element'){
		$this->options = $options;
		$this->type = $type;
		if($this->options['validate'] && !is_array($this->options['validate']))
			$this->options['validate'] = array($this->options['validate']);
		if(!$this->options['id'])
			$this->options['id'] = $this->options['name'].'_'.(self::$uniques++);
		elseif(!$this->options['name'])
			$this->options['name'] = $this->options['id'];
	}
	public function get(){
		return $this->html;
	}
	public static function implode($a, $options = array(
		'skipValue' => false,
		'skipId' => false,
		'skipName' => false,
	)){
		if(!is_array($a)) return '';
		foreach($a as $key => $val){
			if(($options['skipValue'] && $key=='value') || ($options['skipId'] && $key=='id') || ($options['skipName'] && $key=='name'))
				continue;
			if(in_array($key, self::$skipAttributes))
				continue;
			
			$s[] = $key.'="'.$val.'"';
		}
		return is_array($s) ? implode(' ', $s) : '';
	}
	public function setValue($v){
		$this->options['value'] = $v;
	}
	public function getEvents($helper){
		if(!is_array($this->options['events']))
			return;
		$els = array();
		foreach($this->options['events'] as $key => $ev){
			$event = !db::numeric($key) && $key!==0 ? $ev : 'start';
			$els[] = "$('".$this->options['id']."').addEvent('".(!db::numeric($key) && $key!==0 ? $key : $ev)."', ".(strpos($event, '.') ? $event : $helper.".".$event).".bindWithEvent(".$helper.", $('".$this->options['id']."')));";
		}
		return implode($els);
	}
}
/* ELEMENTS ClASS */
class elements extends element {
	private $elements = array();
	public function __construct(){
		$this->elements = func_get_args();
		if(is_array($this->elements[0]))
			$this->elements = $this->elements[0];
	}
	public function addElement($el){
		array_push($this->elements, $el);
		return $el;
	}
	public function get($tpl = null, $vars = array()){
		$els = array();
		foreach($this->elements as $el)
			if(!in_array($el->options['type'], array('field')) && !$el->options['skip'])
				$els[$el->options['name']] = $el->get();
		
		if($tpl)
			$this->html = template::getInstance($tpl)->assign($els)->assign($vars)->parse(true);
		else
			$this->html = implode($els);
		
		return $this->html;
	}
}

/* INPUT CLASS */
class input extends element {
	public function __construct($options){
		parent::__construct($options, 'input');
	}
	public function get(){
		if(!$this->options['type'])
			$this->options['type'] = 'text';
		if($this->options['type']=='hidden')
			$this->options['nobreak'] = true;
		$this->html = ($this->options['type']!='hidden' && $this->options['caption'] ? '<span class="b">'.$this->options['caption'].'</span>'.(!$this->options['nobreak'] ? '<br/>' : '') : '').'
				<input '.self::implode($this->options).'/>'.(!$this->options['nobreak'] ? '<br/>' : '');
		return $this->html;
	}
}

/* BUTTON CLASS */
class button extends element {
	public function __construct($options){
		parent::__construct($options, 'button');
	}
	public function get(){
		$this->html = '<button '.self::implode($this->options).'>'.$this->options['caption'].'</button>';
		return $this->html;
	}
}

/* FIELD CLASS */
class field extends element {
	public function __construct($options){
		parent::__construct($options, 'field');
	}
	public function get(){
		return '';
	}
}

/* RADIO CLASS */
class radio extends element {
	public function __construct($options){
		$options['type'] = 'radio';
		parent::__construct($options, 'radio');
	}
	public function get(){
		foreach($this->options['elements'] as $val)
			$this->html[] = '<label><input class="radio" value="'.$val['value'].'" '.self::implode($this->options, array('skipValue' => true, 'skipId' => true)).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$val['caption'].'</label>';
		
		$this->html = implode($this->html);
		if($this->options['caption'])
			$this->html = '<span class="b">'.$this->options['caption'].'</span><br/>
				<div id="'.$this->options['id'].'" class="leftm bottomm">'.$this->html.'</div>';
		
		return $this->html;
	}
}

/* SELECT CLASS */
class select extends element {
	public function __construct($options){
		parent::__construct($options, 'select');
	}
	public function get(){
		foreach($this->options['elements'] as $val)
			$this->html[] = '<option value="'.$val['value'].'" '.self::implode($this->options, array('skipValue' => true, 'skipId' => true, 'skipName' => true)).($val['value']==$this->options['value'] ? ' selected="selected"' : '').'> '.$val['caption'].'</option>';
		
		$this->html = implode($this->html);
		if($this->options['caption'])
			$this->html = '<span class="b">'.$this->options['caption'].'</span><br/>
				<div><select '.self::implode($this->options, array('skipValue' => true)).'>'.$this->html.'</select></div>';
		
		return $this->html;
	}
}

/* TEXTAREA CLASS */
class textarea extends element {
	public function __construct($options){
		parent::__construct($options, 'textarea');
	}	
	public function get(){
		$this->html = ($this->options['caption'] ? '<span class="b">'.$this->options['caption'].'</span>'.(!$this->options['nobreak'] ? '<br/>' : '') : '').'
				<textarea '.self::implode($this->options, array('skipValue' => true)).' rows="0" cols="0">'.$this->options['value'].'</textarea><br/>';
		return $this->html;
	}
}

/* CHECKBOX CLASS */
class checkbox extends element {
	public function __construct($options){
		$options['type'] = 'checkbox';
		parent::__construct($options, 'checkbox');
	}
	public function get(){
		$this->html = '<label><input class="check" value="'.$this->options['default'].'" '.self::implode($this->options, array('skipValue' => true)).' '.($this->options['default']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$this->options['caption'].'</label>';
		return $this->html;
	}
}
?>