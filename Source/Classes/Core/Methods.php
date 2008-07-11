<?php
	
	function pick($a, $b){
		return $a ? $a : $b;
	}
	
	function endsWith($string, $look){
		return strrpos($string, $look)===strlen($string)-strlen($look);
	}
	
	function startsWith($string, $look){
		return strpos($string, $look)===0;
	}