<?php
/*
 * Styx::CSSContent - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: CSS content-type
 *
 */

class CSSContent extends ContentType {

	public function isExtended(){
		return true;
	}
	
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/css; charset=UTF-8',
		);
	}
	
	public function process($content){
		return PackageManager::compress($content);
	}
	
}