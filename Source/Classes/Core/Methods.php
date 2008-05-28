<?php
	abstract class Runner {
		
		public function execute(){
			include(func_get_arg(0));
		}
		
	}
	
	function array_remove(&$array, $value){
		$i = array_search($value, $array);
		if($i!==false) unset($array[$i]);
	}
	
	function array_flatten(&$array, $prefix = null){
		$imploded = array();
		if($prefix) $prefix .= '.';
		
		foreach($array as $key => $val){
			if(is_array($val))
				$imploded = array_merge($imploded, array_flatten($val, $prefix.$key));
			else
				$imploded[$prefix.$key] = $val;
		}
		return $array = $imploded;
	}
	
	function array_extend(&$src, $extended){
		if(!is_array($extended))
			return $src;
		
		foreach($extended as $key => $val)
			$src[$key] = is_array($val) ? array_extend($src[$key], $val) : $val;
		
		return $src;
	}
	
	function splat(&$array){
		return $array = !is_array($array) ? (is_null($array) ? array() : array($array)) : $array;
	}
	
	function pick($a, $b){
		return $a ? $a : $b;
	}
	
	function endsWith($string, $look){
		return strrpos($string, $look)===strlen($string)-strlen($look);
	}
	
	function startsWith($string, $look){
		return strpos($string, $look)===0;
	}