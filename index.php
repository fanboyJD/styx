<?php
	require_once('./Classes/Base/envClass.php');
	require_once('./Classes/Base/cacheClass.php');
	require_once('./Classes/Base/utilClass.php');
	
	Env::initialize();
	
	require_once('./Config/Configuration.php');
	
	function class_file_exists($class){
		$class = strtolower($class);
		
		$Classes = Env::retrieve('Classes');
		if(class_exists($class) || $Classes[$class.'Class'])
			return $Classes[$class.'Class'];
	}
	
	function __autoload($class){
		$file = class_file_exists($class);
		
		if(!class_exists($class) && $file)
			require_once($file);
		
		return true;
	}
?>