<?php

require_once('./Initialize.php');

class DataComparisonTest extends StyxUnitTest {
	
	public function testComparison(){
		$data = array(
			array('id' => 1, 'title' => 'C'),
			array('id' => 2, 'title' => 'D', 'test' => 2),
			array('id' => 3, 'title' => 'A', 'test' => 3),
			array('id' => 4, 'title' => 'B'),
		);
		
		$DataComparison = new DataComparison;
		
		$DataComparison->setField('id');
		
		uasort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(0, 1, 2, 3));
		
		$DataComparison->setOrder(false);
		
		uasort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(3, 2, 1, 0));
		
		$DataComparison->setField('title')->setOrder(true);
		
		uasort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(2, 3, 0, 1));
		
		$DataComparison->setField('test');
		
		uasort($data, array($DataComparison, 'sort'));
		$this->assertEqual(array_keys($data), array(1, 2, 3, 0));
	}

}