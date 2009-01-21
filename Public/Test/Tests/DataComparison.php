<?php

require_once('./Initialize.php');

class DataComparisonTest extends UnitTestCase {
	
	public function testComparison(){
		$data = array(
			array('id' => 1, 'title' => 'C'),
			array('id' => 2, 'title' => 'D', 'test' => 2),
			array('id' => 3, 'title' => 'A', 'test' => 3),
			array('id' => 4, 'title' => 'B'),
		);
		
		$DataComparison = new DataComparison($data);
		
		$DataComparison->setField('id');
		
		uksort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(0, 1, 2, 3));
		
		$DataComparison->setOrder(false);
		
		uksort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(3, 2, 1, 0));
		
		$DataComparison->setField('title')->setOrder(true);
		
		uksort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(2, 3, 0, 1));
		
		$DataComparison->setField('test')->setOrder(true);
		
		uksort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(1, 2, 3, 0));
	}

}