<?php
	include('../Assets/SimpleTest/autorun.php');
	$use = 'debug';
	
	$Paths = array(
		'app.path' => realpath('../../Sample/'),
		'app.public' => realpath('./'),
	);
	
	include('../../../Source/Styx.php');
	
	function initialize(){
		Route::connect('logout', array(
			'layer' => 'login',
			'event' => 'logout',
		));
	}