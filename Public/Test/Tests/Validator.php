<?php

require_once('./Initialize.php');

class ValidatorTest extends StyxUnitTest {
	
	public function testCall(){
		$this->assertTrue(Validator::call('data', 'some_nonexistend_validator'));
		
		$this->assertEqual(Validator::call('data', 'id'), 'id');
		
		$this->assertTrue(Validator::call(5, 'id'));
	}
	
	public function testId(){
		$this->assertTrue(Validator::id(5));
		
		$this->assertTrue(Validator::id('5'));
		
		$this->assertFalse(Validator::id(' 5 '));
		
		$this->assertTrue(Validator::id(10, 5));
		$this->assertTrue(Validator::id(11, 5));
		$this->assertTrue(Validator::id(14, 5));
		$this->assertTrue(Validator::id(15, 5));
		
		$this->assertFalse(Validator::id('5a'));
		$this->assertFalse(Validator::id('a5'));
	}
	
	public function testNumericrange(){
		$this->assertTrue(Validator::numericrange(5, array(1, 10)));
		
		$this->assertFalse(Validator::numericrange(15, array(1, 10)));
		
		$this->assertFalse(Validator::numericrange('a', array(1, 10)));
		
		$this->assertTrue(Validator::numericrange(5, array(5, 10)));
		$this->assertFalse(Validator::numericrange(5, array(6, 10)));
	}
	
	public function testDate(){
		$this->assertTrue(Data::date('12.01.2009'));
		
		$this->assertTrue(Data::date('01.12.2009', array('order' => 'mdy')));
		
		$this->assertFalse(Data::date('01/12/2009', array('order' => 'mdy')));
		$this->assertTrue(Data::date('01/12/2009', array('order' => 'mdy', 'separator' => '/')));
		
		$day = date('j', time()+86400);
		$month = date('n', time()+86400);
		$year = date('Y', time()+86400);
		$tomorrow = mktime(0, 0, 0, $month, $day, $year);
		
		$this->assertFalse(Data::date($day.'.'.$month.'.'.$year));
		$this->assertTrue(Data::date($day.'.'.$month.'.'.$year, array('future' => true)));
		$this->assertTrue(Data::date('12.01.2009', array('future' => true)));
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