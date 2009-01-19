<?php
/**
 * Styx::HTMLContent - For HTML-Output
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class HTMLContent extends ContentType {
	
	/**
	 * Returns headers for HTML
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/html; charset=UTF-8',
		);
	}
	
	/**
	 * Flattens the input so there is only a single-dimensional array
	 *
	 * @return array
	 */
	public function process($content){
		return Hash::flatten($content);
	}
	
}