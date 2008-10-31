<?php
/*
 * Styx::XMLContent - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: XML content-type
 *
 */

class XMLContent extends ContentType {
	
	public function getHeaders(){
		return array(
			'Content-Type' => 'application/xhtml+xml; charset=UTF-8',
		);
	}
	
}