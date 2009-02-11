<?php
/**
 * Styx::JSONContent - For JSON-Output
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class JSONContent extends ContentType {
	
	/**
	 * Returns headers for JSON
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array(
			'Content-Type' => 'application/json; charset=UTF-8',
		);
	}
	
	/**
	 * Encodes the output for json and removes unnecessary whitespace
	 *
	 * @param array $content
	 * @return array
	 */
	public function process($content){
		return Data::encode($content);
	}
	
}