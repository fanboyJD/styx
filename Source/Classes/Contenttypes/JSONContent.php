<?php
/*
 * Styx::JSONContent - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: JSON content-type
 *
 */

class JSONContent extends ContentType {
	
	public function getHeaders(){
		return array(
			'Content-Type' => 'application/json; charset=UTF-8',
		);
	}
	
	public function process($content){
		return Data::encode($content, array(
			'whitespace' => 'clean',
		));
	}
	
}