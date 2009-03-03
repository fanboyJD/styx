<?php

require_once('./Initialize.php');

class UserObject extends Object {
	
	protected function initialize(){
		return array(
			'structure' => array(
				'id' => array(
					':caption' => 'Id',
					':public' => true,
					':validate' => array(
						'id' => true,
					),
				),
				'name' => array(
					':caption' => 'Username',
					':public' => true,
					':validate' => array(
						'pagetitle' => true,
						'notempty' => true,
					),
				),
				'job' => array(
					':caption' => 'Job description',
					':public' => true,
					':validate' => array(
						'sanitize' => true,
					),
				),
				'time' => array(
					':public' => true,
					':default' => time(),
					':validate' => array(
						'id' => true,
					),
				),
			),
		);
	}
	
}

class ObjectTest extends UnitTestCase {
	
	public function testUserObject(){
		$user = new UserObject(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => 'Server Administrator',
		));
		
		$this->assertEqual($user['id'], 1);
		$this->assertEqual($user['name'], 'Admin');
		
		$this->assertFalse(isset($user['blah']));
		$this->assertTrue(isset($user['id']));
		$this->assertFalse(empty($user['id']));
		
		$user['id'] = 5;
		
		$this->assertEqual($user['id'], 5);
		
		$user['blah'] = 'Testvalue should not be set';
		$this->assertEqual(count($user), 4);
		$this->assertNull($user['blah']);
	}
	
	public function testSave(){
		$user = new UserObject(array(
			'id' => 'Test',
			'name' => 'Admin',
			'job' => 'Server Administrator',
		));
		
		$exception = false;
		try{
			// Should throw an exception because id can't be 'Test'
			$user->save();
		}catch(ValidatorException $e){
			$exception = true;
		}
		$this->assertTrue($exception);
		
		$user = new UserObject(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
		));
		
		$user->save();
		
		// Job should now be clean
		$this->assertEqual($user['job'], '&gt;Sanitized&lt;');
	}

	public function testToArray(){
		$user = new UserObject(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
		));
		
		$this->assertEqual($user->toArray(), array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
			'time' => time(),
		));
		
		$user->save();
		
		$this->assertEqual($user->toArray(), array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '&gt;Sanitized&lt;',
			'time' => time(),
		));
	}
	
	public function testNewsObject(){
		$news = new NewsObject(Database::select('news')->where(array('id' => 1))->fetch(), false);
		
		$this->assertEqual($news->getTable(), 'news');
		
		$this->assertEqual($news['title'], 'Newsheadline');
		
		$newEntry = new NewsObject(array(
			'title' => 'This is some interesting title',
		));
		
		$exception = false;
		try{
			// Should throw an exception because "content" is empty
			$newEntry->save();
		}catch(ValidatorException $e){
			$exception = true;
		}
		$this->assertTrue($exception);
		
		$newEntry['content'] = '<b Malicious HTML';
		
		$newEntry->save();
		// The content should have been cleaned
		$this->assertEqual($newEntry['content'], '<b></b>');
		
		// We don't know the id but it's gotta be greater than 0
		$this->assertTrue($newEntry['id'] > 0);
		$id = $newEntry['id'];
		$newEntry->delete();
		$this->assertNull($newEntry['title']);
		
		// Double Check if it really is gone
		$this->assertFalse(Database::select('news')->where(array('id' => $id))->fetch());
	}
	
	public function testPagetitle(){
		// New Object, but one with pagetitle "Title" is already available so it should save as "Test_1"
		$newsEntry = new NewsObject(array(
			'title' => 'Test',
			'content' => 'This is a test',
		));
		$newsEntry->save();
		
		$this->assertEqual($newsEntry['pagetitle'], 'Test_1');
		
		// We select this object and check if it modifies the pagetitle (it should not!)
		$newsEntry2 = new NewsObject(Database::select('news')->where(array('id' => $newsEntry['id']))->fetch(), false);
		$newsEntry2['content'] = 'Changed Content';
		$newsEntry2->save();
		
		$this->assertEqual($newsEntry2['pagetitle'], 'Test_1');
		
		// And now we create one with title Test again, should be saved as pagetitle Test_2
		$newsEntry3 = new NewsObject(array(
			'title' => 'Test',
			'content' => 'This is a test',
		));
		
		$newsEntry3->save();
		$this->assertEqual($newsEntry3['pagetitle'], 'Test_2');
		
		$ids = array($newsEntry['id'], $newsEntry3['id']);
		$newsEntry->delete();
		$newsEntry3->delete();
		
		// There shouldn't be any news left
		$this->assertEqual(Database::select('news')->where(Query::in('id', $ids))->quantity(), 0);
	}
	
}