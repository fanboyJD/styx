<?php
/**
 * Styx::ContentType - Base class for all Output-Types
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class ContentType {
	
	/**
	 * The name of the content-type (e.g. html, xml, css)
	 *
	 * @var string
	 */
	protected $type = null;
	
	/**
	 * Sets up the contenttype
	 *
	 */
	public function __construct(){
		$this->type = String::sub(String::toLower(get_class($this)), 0, -7);
	}
	
	/**
	 * Returns the name of the content-type
	 *
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}
	
	/**
	 * Extended-Types cannot be used for normal site-output
	 *
	 * @return bool
	 */
	public function isExtended(){
		return false;
	}
	
	/**
	 * Returns the headers to be used for the content-type
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array();
	}
	
	/**
	 * Processes all data before the output happens
	 *
	 * @param array $content
	 * @return array
	 */
	public function process($content){
		return $content;
	}
	
}