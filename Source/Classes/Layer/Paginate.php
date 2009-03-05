<?php
/**
 * Styx::Paginate - Dissects contents to several pages
 *
 * @package Styx
 * @subpackage Layer
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Paginate {
	
	/**
	 * A QuerySelect or DataSet instance
	 *
	 * @var QuerySelect
	 */
	protected $Data;
	/**
	 * The Layer instance (mainly to generate the links)
	 *
	 * @var Layer
	 */
	protected $Layer;
	/**
	 * The Model instance
	 *
	 * @var Model
	 */
	protected $Model;
		
	/**
	 * Options
	 *
	 * @var array
	 */
	protected $options = array(
		'start' => 0,
		'per' => 10,
		'key' => 'start',
		'link' => null,
		'title' => null,
		'options' => array(),
		'template' => null,
	);
		
	/**
	 * Holds the number of total elements in the given set
	 *
	 * @var int
	 */
	protected $count = 0;

	/**
	 * Initializes the pagination class
	 *
	 * @param QuerySelect|DataSet $data
	 * @param array $options
	 */
	public function initialize($data, $options = array()){
		$this->Data = $data;
		
		Hash::extend($this->options, $options);
		
		$this->options['per'] = Data::id($this->options['per']);
		
		if($this->Layer && !$this->options['start'] && !empty($this->Layer->get[$this->options['key']]))
			$this->options['start'] = $this->Layer->get[$this->options['key']];
		
		$this->options['start'] = Data::id($this->options['start'], $this->options['per']);
	}
	
	/**
	 * Either returns an instance of this class or of a subclass of this class
	 *
	 * @param string $class
	 * @return Paginate
	 */
	public static function retrieve($class){
		$self = strtolower(__CLASS__);
		if(!$class || !Core::classExists($class) || (strtolower($class)!=$self && !is_subclass_of($class, $self)))
			$class = $self;
		
		return new $class;
	}
	
	/**
	 * Parses and returns output for the pagination
	 *
	 * @return string
	 */
	public function parse(){
		if(!$this->Data) return;
		
		$this->count = $this->Data->quantity($this->Model->getIdentifier('internal'));
		
		if($this->options['start']>=$this->count)
			$this->options['start'] = 0;
		
		$this->Data->limit($this->options['start'], $this->options['per']);
		
		if($this->count<=$this->options['per'] && !$this->options['start']) return;
		
		return Template::map(pick($this->options['template'], array('Paginate', 'Default.php')))->bind($this->Layer)->parse(true);
	}
	
	/**
	 * Returns the identifier for the previous page
	 *
	 * @return int
	 */
	public function getPrevious(){
		return floor($this->options['start']/$this->options['per'])>=1 ? $this->options['start']-$this->options['per'] : false;
	}
	
	/**
	 * Returns the identifier for the next page
	 *
	 * @return int
	 */
	public function getNext(){
		return $this->count-$this->options['per']>$this->options['start'] ? $this->options['start']+$this->options['per'] : false;
	}
	
	/**
	 * Returns the identifier for the current page
	 *
	 * @return int
	 */
	public function getCurrent(){
		return floor($this->options['start']/$this->options['per'])+1;
	}
	
	/**
	 * Returns the number of all pages
	 *
	 * @return int
	 */
	public function getCount(){
		return ceil($this->count/$this->options['per']);
	}

	/**
	 * Returns the page as requested by the user
	 *
	 * @return int
	 */
	public function getStart(){
		return $this->options['start'];
	}
	
	/**
	 * Returns how many elements to show per page
	 *
	 * @return int
	 */
	public function getPer(){
		return $this->options['per'];
	}

	/**
	 * Retrieves the used key (defaults to "start") for the link
	 *
	 * @return string
	 */
	public function getKey(){
		return $this->options['key'];
	}
	
	/**
	 * Can be used for an optional Layer to base the links on
	 *
	 * @return string
	 */
	public function getLink(){
		return $this->options['link'];
	}
	
	/**
	 * Returns the title for the given page, if any
	 *
	 * @return string
	 */
	public function getTitle(){
		return $this->options['title'];
	}
	
	/**
	 * Returns additional options for the link
	 *
	 * @return array
	 */
	public function getLinkOptions(){
		return $this->options['options'];
	}
	
	/**
	 * Binds a class (usually a Layer) to the template
	 *
	 * @return Paginate
	 */
	public function bind($bind){
		if(is_object($bind)) $this->Layer = $bind;
		
		return $this;
	}
	
	/**
	 * Binds a Model to the template
	 *
	 * @return Paginate
	 */
	public function model($model){
		if(is_object($model)) $this->Model = $model;
		
		return $this;
	}
	
}