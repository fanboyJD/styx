<?php

require_once('./Initialize.php');

class DataSetTest extends UnitTestCase {
	
	public function testSet(){
		$data = array(
			array('id' => 1, 'parent' => 9),
			array('id' => 2, 'parent' => 0),
			array('id' => 3, 'parent' => 5),
			array('id' => 4, 'parent' => 0),
			array('id' => 5, 'parent' => 2),
			array('id' => 6, 'parent' => 2),
			array('id' => 7, 'parent' => 1),
			array('id' => 8, 'parent' => 2),
			array('id' => 9, 'parent' => 5),
			array('id' => 10, 'parent' => 9),
			array('id' => 11, 'parent' => 6),
			array('id' => 12, 'parent' => 11),
		);
		
		$set = new DataSet($data);
		$set->limit(1, 2)->fields('id');
		
		$i = 2;
		foreach($set as $v)
			$this->assertEqual($v, array('id' => $i++));
		
		$set = new DataSet($data);
		$set->limit(4, 10);
		
		$this->assertEqual(count($set), 8);
		
		$set = new DataSet($data);
		$set->order('id DESC')->limit(2);
		
		$i = 12;
		foreach($set as $v)
			$this->assertEqual($v['id'], $i--);
	}

}