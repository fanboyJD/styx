<?php
/**
 * Styx::JavaScriptContent - For JavaScript-Output
 *
 * @package Styx
 * @subpackage ContentType
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class JavaScriptContent extends ContentType {
	
	/**
	 * JavaScript is not one of the default types to be used to display a page
	 *
	 * @return bool
	 */
	public function isExtended(){
		return true;
	}
	
	/**
	 * Returns headers for JavaScript
	 *
	 * @return array
	 */
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/javascript; charset=UTF-8',
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