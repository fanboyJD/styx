<?php
	include('../Assets/SimpleTest/autorun.php');
	include('../Assets/SimpleTest/browser.php');
	
	$Paths = array(
		'app.path' => realpath('../../Sample/'),
		'app.public' => realpath('./'),
	);
	
	include('../../../Source/Styx.php');