<?php
/*
 * Styx::JavaScriptContent - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: JavaScript content-type
 *
 */

class JavaScriptContent extends ContentType {
	
	public function isExtended(){
		return true;
	}
	
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/javascript; charset=UTF-8',
		);
	}
	
	public function process($content){
		return PackageManager::compress($content);
	}
	
}