<?php
	chdir('../Public');
	include('index.php');
	
	function is($a, $b = null){
		$expr = ($a==$b);
		echo '<div style="color: '.($expr ? '#008800' : '#880000').';">'.(!$a ? 'null' : print_r($a, 1)).' should be '.(!$b ? 'null' : print_r($b, 1)).'. Expression: '.print_r($expr, 1).'</div>';
	}
	
	/* @var $c Cache */
	$c = Cache::getInstance();
	
	is($c->getEngine(), array('type' => 'eaccelerator'));
	
	$c->eraseAll();
	is($c->retrieve('user', 'test'));
	
	$c->store('user', 'test', 'Asdf');
	is($c->retrieve('user', 'test'), 'Asdf');
	
	$c->erase('user', 'test');
	is($c->retrieve('user', 'test'));
	
	$c->store('user', 'test', 'Asdf');
	$c->eraseBy('user', 'te');
	is($c->retrieve('user', 'test'));
	
	$c->store('user', 'test', 'Asdf');
	$c->eraseBy('user', 'test');
	is($c->retrieve('user', 'test'));
	
	$c->store('user', 'test', 'Asdf');
	$c->eraseAll();
	is($c->retrieve('user', 'test'));
	
	$c->store('user', 'test', 'Asdf');
	$c->store('user', 'test', 'Hello');
	is($c->retrieve('user', 'test'), 'Hello');
	
	echo '<br/><b>FileCache</b>';
	$c->store('user', 'hello', 'Yea', 'file');
	$c->erase('user', 'hello');
	is($c->retrieve('user', 'hello'));
	is($c->retrieve('user', 'hello', 'file'), 'Yea');
	$c->erase('user', 'hello', 'file');
	is($c->retrieve('user', 'hello', 'file'));