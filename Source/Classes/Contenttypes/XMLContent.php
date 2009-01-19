<?php
/**
 * Styx::XMLContent - For XML-Output
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class XMLContent extends HTMLContent {
	
	/**
	 * Returns headers for XML
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array(
			'Content-Type' => 'application/xhtml+xml; charset=UTF-8',
		);
	}
	
}