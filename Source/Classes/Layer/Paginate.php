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
	 * The Layer instance (mainly to generate the links)
	 *
	 * @var Layer
	 */
	protected $Layer;
	/**
	 * A Model, QuerySelect or DataSet to operate on
	 *
	 * @var Model|QuerySelect|DataSet
	 */
	protected $Object;
		
	/**
	 * The criteria matching the data
	 *
	 * @var array
	 */
	protected $criteria;
	
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
	 * @param array $criteria
	 * @param array $options
	 * @return Paginate
	 */
	public function initialize($criteria, $options = array()){
		$this->criteria = $criteria;
		Hash::extend($this->options, $options);
		
		$this->options['per'] = Data::id($this->options['per']);
		
		if($this->Layer && !$this->options['start'] && !empty($this->Layer->get[$this->options['key']]))
			$this->options['start'] = $this->Layer->get[$this->options['key']];
		
		$this->options['start'] = Data::id($this->options['start'], $this->options['per']);
		
		return $this;
	}
	
	/**
	 * Either returns an instance of this class or of a subclass of this class
	 *
	 * @param string $class
	 * @return Paginate
	 */
	public static function retrieve($class = null){
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
		if(!$this->Object) return;
		
		$isModel = $this->Object instanceof Model;
		
		if($isModel) $query = $this->Object->select();
		else $query = $this->Object;
		
		$this->count = $query->setCriteria($this->criteria)->quantity($isModel ? $this->Object->getIdentifier('internal') : (!empty($this->options['identifier']) ? $this->options['identifier'] : null));
		
		if($this->options['start']>=$this->count)
			$this->options['start'] = 0;
		
		$this->criteria['limit'] = array($this->options['start'], $this->options['per']);
		
		if($isModel) $this->Object->findMany($this->criteria);
		else $this->Object->setCriteria($this->criteria);
		
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
	 * Binds an object to the pagination
	 *
	 * @return Paginate
	 */
	public function object($object){
		if(is_object($object)) $this->Object = $object;
		
		return $this;
	}
	
}