<?php
	if(empty($Helper)){
		include('../Assets/SimpleTest/autorun.php');
		include('../Assets/SimpleTest/browser.php');
		include('./StyxReporter.php');
	}
	
	$Paths = array(
		'app.path' => realpath('../../Sample/'),
		'app.public' => realpath('./'),
	);
	
	include('../../../Source/Styx.php');