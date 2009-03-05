<?php

require_once('./Initialize.php');

class StringTest extends StyxUnitTest {
	
	public function testEnds(){
		if(Core::retrieve('feature.mbstring')){
			$this->assertTrue(String::ends('Hellö', 'ö'));
			$this->assertFalse(String::ends('Hellö', 'o'));
			$this->assertFalse(String::ends('Hellö', 'H'));
		}
	}
	
	public function testStarts(){
		if(Core::retrieve('feature.mbstring')){
			$this->assertTrue(String::starts('Äpfel', 'Ä'));
			$this->assertFalse(String::starts('Äpfel', 'A'));
			$this->assertFalse(String::starts('Äpfel', 'p'));
		}
		
		$this->assertTrue(String::starts('Hello', 'H'));
		$this->assertFalse(String::starts('Hello', 'h'));
	}
	
	public function testUcfirst(){
		$this->assertEqual(String::ucfirst('sTring'), 'String');
		
		if(Core::retrieve('feature.mbstring'))
			$this->assertEqual(String::ucfirst('äPFEL'), 'Äpfel');
	}
	
	public function testString(){
		if(Core::retrieve('feature.mbstring')){
			/* We only make sure substr behaves wrong and mb_string is used instead */
			$this->assertNotEqual(substr('Die Äpfel', 0, 5), 'Die Ä');
			
			$this->assertEqual(String::sub('Die Äpfel', 0, 5), 'Die Ä');
			
			$this->assertEqual(String::sub('Die Äpfel', 0, 6), 'Die Äp');
		}
	}
	
	public function testClean(){
		$this->assertEqual(String::clean(array(
			'null' => 0,
			'int' => 3,
			'strint' => '31',
			'float' => 1.03,
			'strfloat' => '1.03',
			'string' => 'Test',
			'nil' => null,
			'remove' => ' ',
			'clean' => "Te\nst",
		), true), array(
			'null' => 0,
			'int' => 3,
			'strint' => 31,
			'float' => 1.03,
			'strfloat' => 1.03,
			'string' => 'Test',
			'clean' => "Te\nst",
		));
		
		$this->assertEqual(String::clean("Te\ns\tt\t"), "Te\nst");
		
		$this->assertEqual(String::clean("Te\ns\tt\t", false), "Te\ns\tt");
		
		$this->assertEqual(String::clean("Te\ns\tt\t", 'clean'), "Te st");
	}
	
	public function testConvert(){
		if(Core::retrieve('feature.iconv')){
			$str = substr('Die Äpfel', 0, 5);
			
			$this->assertEqual(String::convert($str), 'Die ');
		}
	}
	
}