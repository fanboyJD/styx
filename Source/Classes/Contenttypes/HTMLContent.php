<?php
/*
 * Styx::HTMLContent - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: HTML content-type
 *
 */

class HTMLContent extends ContentType {
	
	public function getHeaders(){
		return array(
			'Content-Type' => 'text/html; charset=UTF-8',
		);
	}
	
	public function process($content){
		return Hash::flatten($content);
	}
	
}