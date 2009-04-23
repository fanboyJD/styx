<?php

require_once('./Initialize.php');

class RequestTest extends StyxUnitTest {
	
	public function testMethod(){
		$b = new StyxBrowser();
		$b->get($this->getTestURL().'Helper/Request.Method.php');
		
		$this->assertEqual($b->getContent(), 'get');
		
		$b->post($this->getTestURL().'Helper/Request.Method.php');
		$this->assertEqual($b->getContent(), 'post');
		
		$b->put($this->getTestURL().'Helper/Request.Method.php');
		$this->assertEqual($b->getContent(), 'put');
		
		$b->delete($this->getTestURL().'Helper/Request.Method.php');
		$this->assertEqual($b->getContent(), 'delete');
	}
	
	public function testData(){
		$b = new StyxBrowser();
		$b->get($this->getTestURL().'Helper/Request.Data.php/Test/Data'.Core::retrieve('path.separator').'1');
		
		$this->assertEqual(json_decode($b->getContent(), true), array(
			'method' => 'get',
			'data' => array('Test' => null, 'Data' => 1),
			'get' => array('Test' => null, 'Data' => 1),
		));
		
		$b = new StyxBrowser();
		$b->post($this->getTestURL().'Helper/Request.Data.php', array(
			'Test' => 1,
		));
		$this->assertEqual(json_decode($b->getContent(), true), array(
			'method' => 'post',
			'data' => array('Test' => 1),
			'get' => null,
		));
		
		$b = new StyxBrowser();
		$b->put($this->getTestURL().'Helper/Request.Data.php/getvariable', array(
			'Test' => 1,
		));
		$this->assertEqual(json_decode($b->getContent(), true), array(
			'method' => 'put',
			'data' => array('Test' => 1),
			'get' => array('getvariable' => null)
		));
		
		$b = new StyxBrowser();
		$b->delete($this->getTestURL().'Helper/Request.Data.php/1', array(
			'Test' => 1,
		));
		$this->assertEqual(json_decode($b->getContent(), true), array(
			'method' => 'delete',
			'data' => null,
			'get' => array('1' => null)
		));
	}
	
}