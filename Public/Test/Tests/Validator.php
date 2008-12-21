<?php

require_once('./Initialize.php');

class ValidatorTest extends UnitTestCase {
	
	public function testCall(){
		$this->assertTrue(Validator::call('data', 'some_nonexistend_validator'));
		
		$this->assertEqual(Validator::call('data', 'id'), 'id');
		
		$this->assertTrue(Validator::call(5, 'id'));
	}
	
	public function testMail(){
		$this->assertTrue(Validator::call('test@og5.net', 'mail'));
		
		$this->assertTrue(Validator::call('someone_noone@abc.museum', 'mail'));
		
		$this->assertEqual(Validator::call('@og5.net', 'mail'), 'mail');
		
		$this->assertEqual(Validator::call('test@og5.', 'mail'), 'mail');
		
		$this->assertEqual(Validator::call('test@og5', 'mail'), 'mail');
		
		$this->assertEqual(Validator::call('test@og.5', 'mail'), 'mail');
		
		$this->assertEqual(Validator::call('te\st@og.5', 'mail'), 'mail');
		
		$this->assertEqual(Validator::call('tes"t@og.5', 'mail'), 'mail');
	}
	
	public function testNotempty(){
		$this->assertTrue(Validator::call('Hello World', 'notempty'));
		
		$this->assertEqual(Validator::call('    ', 'notempty'), 'notempty');
	}
	
	public function testLength(){
		$this->assertTrue(Validator::call('Hello World', array('length' => array(12))));
		
		$this->assertTrue(Validator::call('Hello World', array('length' => array(2, 13))));
		
		$this->assertEqual(Validator::call('Hello World', array('length' => array(3))), 'length');
		
		$this->assertEqual(Validator::call('Hello World', array('length' => array(11, 15))), 'length');
	}
	
}