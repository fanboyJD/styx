<?php
	include('Config/Configuration.php');
	
	$_CONFIGURATION['appPath'] = realpath('./');
	
	chdir(realpath($_CONFIGURATION['path']));

	include('./index.php');
?>