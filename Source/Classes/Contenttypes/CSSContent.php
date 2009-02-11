<?php
/**
 * Styx::CSSContent - For CSS-Output
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class CSSContent extends ContentType {

	/**
	 * CSS is not one of the default types to be used to display a page
	 *
	 * @return bool
	 */
	public function isExtended(){
		return true;
	}
	
	/**
	 * Returns headers for CSS
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/css; charset=UTF-8',
		);
	}
	
	/**
	 * Compresses the input and removes unnecessary whitespaces
	 *
	 * @param array $content
	 * @return string
	 */
	public function process($content){
		return PackageManager::compress($content);
	}
	
}