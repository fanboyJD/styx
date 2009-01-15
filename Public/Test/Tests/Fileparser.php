<?php

require_once('./Initialize.php');

class FileparserTest extends UnitTestCase {
	
	public $folder;
	public $original;
	public $file;
	
	public function setUp(){
		$this->original = Core::retrieve('app.path');
		// We hack the app.path folder as Fileparser does not allow files from outside
		// The app folder (which is the Sample-App-Folder in case of the Unit-Tests)
		$this->folder = Core::store('app.path', realpath(Core::retrieve('app.public').'/Temp').'/');
		
		$this->file = 'tmp.file';
		
		touch($this->folder.'/'.$this->file);
	}
	
	public function tearDown(){
		unlink($this->folder.'/'.$this->file);
		
		Core::store('app.path', $this->original);
	}
	
	public function testSingle(){
		file_put_contents($this->folder.'/'.$this->file, "
			key = 'Value';
		");
		
		$Fileparser = new Fileparser($this->file);
		
		$this->assertEqual($Fileparser->retrieve('key'), 'Value');
	}
	
	public function testNamespace(){
		file_put_contents($this->folder.'/'.$this->file, "
			namespace test {
				key = 'Value';
			}
		");
		
		$Fileparser = new Fileparser($this->file);
		
		$this->assertEqual($Fileparser->retrieve('test.key'), 'Value');
	}
	
	public function testOverwrite(){
		// Stuff outside the namespace always overwrites the namespace content
		file_put_contents($this->folder.'/'.$this->file, "
			test.key = 'Value';
			
			namespace test {
				key = 'MyValue';
			}
		");
		
		$Fileparser = new Fileparser($this->file);
		
		$this->assertEqual($Fileparser->retrieve('test.key'), 'Value');
	}
	
	public function testBoth(){
		// A namespace can also have a value
		file_put_contents($this->folder.'/'.$this->file, "
			test = 'Value';
			
			namespace test {
				key = 'Value';
			}
		");
		
		$Fileparser = new Fileparser($this->file);
		
		$this->assertEqual($Fileparser->retrieve('test'), 'Value');
		$this->assertEqual($Fileparser->retrieve('test.key'), 'Value');
	}
	
	public function testInvalid(){
		// An invalid namespace does not affect the others/the outside
		file_put_contents($this->folder.'/'.$this->file, "
			key = 'Value';
			
			namespace test {
				key = 'Value
			}
		");
		
		$Fileparser = new Fileparser($this->file);
		
		$this->assertNull($Fileparser->retrieve('test.key'));
		$this->assertEqual($Fileparser->retrieve('key'), 'Value');
	}
	
}