<?php

include('./Initialize.php');

class HashTest extends UnitTestCase {
	
	public function testLength(){
		
		$this->assertNull(Hash::length(array()));
		
		$this->assertNull(Hash::length('string'));
		
		$this->assertEqual(Hash::length(array(1, 2, 3)), 3);
		
	}
	
	/*public function testRemove(){
		$array = array(0, 5, 'test', array(), array());
		
		Hash::remove($array, 0);
	}*/
	
}