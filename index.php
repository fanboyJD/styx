<?php
	require_once('./Classes/Base/envClass.php');
	require_once('./Classes/Base/cacheClass.php');
	require_once('./Classes/Base/utilClass.php');
	
	require_once('./Config/Configuration.php');
	
	if(is_array($_CONFIGURATION)){
		Env::store($_CONFIGURATION);
		unset($_CONFIGURATION);
	}
	
	Env::initialize(realpath('./'));
	
	function class_file_exists($class){
		$class = strtolower($class);
		
		$Classes = Env::retrieve('Classes');
		if(class_exists($class) || $Classes[$class.'Class'])
			return $Classes[$class.'Class'];
		
		return false;
	}
	
	function __autoload($class){
		$file = class_file_exists($class);
		
		if($file && !class_exists($class))
			require_once($file);
		
		return true;
	}
	
	print_r(Env::retrieve('Classes'));
	Cache::getInstance()->eraseAll();
?>