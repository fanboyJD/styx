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
		
		/* Should contain 2 elements with id 2-4 and only the 'id' field */
		$i = 2;
		foreach($set as $v)
			$this->assertEqual($v, array('id' => $i++));
		
		$set = new DataSet($data);
		$set->limit(4, 10);
		
		/* Should contain 8 elements with id 5-12 */
		$i = 5;
		$this->assertEqual(count($set), 8);
		foreach($set as $v)
			$this->assertEqual($v['id'], $i++);
		
		$set = new DataSet($data);
		$set->order('id DESC')->limit(2);
		
		/* Should contain 2 elements with id 12-10 */
		$i = 12;
		foreach($set as $v)
			$this->assertEqual($v['id'], $i--);
		
		/* Tests Removing and Adding Data from the Set */
		$set = new DataSet(array(
			array('id' => 4),
		));
		
		$set->push(array('id' => 5));
		
		$set->remove(array('id' => 4));
		
		foreach($set as $v)
			$this->assertEqual($v['id'], 5);
		
		$set->pop(1);
		
		$this->assertEqual(count($set), 0);
	}

}