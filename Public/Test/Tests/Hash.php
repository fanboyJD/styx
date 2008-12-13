<?php

require_once('./Initialize.php');

class HashTest extends UnitTestCase {
	
	public function testLength(){
		
		$this->assertNull(Hash::length(array()));
		
		$this->assertNull(Hash::length('string'));
		
		$this->assertEqual(Hash::length(array(1, 2, 3)), 3);
		
	}
	
	public function testRemove(){
		$array = $copy = array(0, 5, 'test', array(), array());
		
		$array = Hash::remove($array, 0);
		
		$this->assertEqual(count($array), 4);
		
		unset($copy[0]);
		$this->assertEqual($array, $copy);
		
		Hash::remove($array, array());
		
		$this->assertEqual(count($array), 2);
		
		unset($copy[3], $copy[4]);
		$this->assertEqual($array, $copy);
	}
	
	public function testFlatten(){
		$array = array(
			'some' => array(
				'multi' => array(
					'dimensional' => array(
						'array' => 'yes',
					),
					'user' => 'system',
					'hello',
				),
			),
		);
		
		Hash::flatten($array);
		
		$this->assertEqual($array, array(
			'some.multi.dimensional.array' => 'yes',
			'some.multi.user' => 'system',
			'some.multi.0' => 'hello',
		));
	}
	
	public function testExtend(){
		$array = array(
			'test' => true,
			'something' => 7,
			'key' => 'value',
		);
		
		Hash::extend($array, array(
			'test' => false,
			'key' => 'differentvalue',
			'new' => 'value',
		));
		
		$this->assertEqual($array, array(
			'test' => false,
			'something' => 7,
			'key' => 'differentvalue',
			'new' => 'value',
		));
		
		$multi = array(
			'test' => 1,
			'key' => array(
				'multi' => array(
					'something' => 7,
					'test' => false,
				),
			),
		);
		
		Hash::extend($multi, array(
			'test' => 2,
			'key' => array(
				'multi' => array(
					'test' => true,
				),
				'one' => 'ten',
			),
		));
		
		$this->assertEqual($multi, array(
			'test' => 2,
			'key' => array(
				'multi' => array(
					'something' => 7,
					'test' => true,
				),
				'one' => 'ten',
			),
		));
	}
	
	public function testSplat(){
		$var = null;
		
		$this->assertIsA(Hash::splat($var), 'array');
		
		$var = array(1);
		
		$this->assertEqual(Hash::splat($var), array(1));
		
		$var = 1;
		
		$this->assertEqual(Hash::splat($var), array(1));
	}
	
	public function testArgs(){
		$var = array(array(1));
		
		$this->assertEqual(Hash::args($var), array(1));
		
		$var = array('a', 'b');
		
		$this->assertEqual(Hash::args($var), array('a', 'b'));
		
		$var = array(array(1), 'a', 'b');
		
		$this->assertEqual(Hash::args($var), array(array(1), 'a', 'b'));
		
		$var = null;
		
		$this->assertEqual(Hash::args($var), array());
		
		$var = 7;
		
		$this->assertEqual(Hash::args($var), array(7));
	}
	
}