<?php

require_once('./Initialize.php');

class DataIteratorTest extends UnitTestCase {
	
	public function testIterator(){
		$data = DataIterator::retrieve(array(
			array('id' => 1, 'parent' => 0),
			array('id' => 2, 'parent' => 0),
			array('id' => 3, 'parent' => 0),
			array('id' => 4, 'parent' => 0),
			array('id' => 5, 'parent' => 2),
			array('id' => 6, 'parent' => 2),
			array('id' => 7, 'parent' => 1),
			array('id' => 8, 'parent' => 5),
			array('id' => 9, 'parent' => 5),
			array('id' => 10, 'parent' => 9),
			array('id' => 11, 'parent' => 10),
			array('id' => 12, 'parent' => 11),
		));
		
		$depths = array(
			1 => 0,
			2 => 0,
			3 => 0,
			3 => 0,
			4 => 0,
			5 => 1,
			6 => 1,
			7 => 1,
			8 => 2,
			9 => 2,
			10 => 3,
			11 => 4,
			12 => 5,
		);
		
		foreach($data as $v)
			$this->assertEqual($data->getDepth(), $depths[$v['id']]);
	}
	
	public function testRecursive(){
		$data = DataIterator::retrieve(array(
			array('id' => 1, 'parent' => 0),
			array('id' => 2, 'parent' => 4),
			array('id' => 3, 'parent' => 2),
			array('id' => 4, 'parent' => 3),
			array('id' => 5, 'parent' => 4),
		), array('current' => 2));
		
		// This should run no more than 4 times and prevent creating an infinite loop
		$i = 0;
		foreach($data as $v)
			$i++;
		
		$this->assertEqual($i, 4);
	}

}
	