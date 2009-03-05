<?php

require_once('./Initialize.php');

class ElementTest extends StyxUnitTest {
	
	public function testElement(){
		$div = new Element(array(
			':caption' => 'Test',
		));
		
		$this->assertEqual($div->get(':caption'), 'Test');
		
		$this->assertEqual($div->format(), '<div>Test</div>');
	}
	
	public function testSingletag(){
		$br = new Element(array(':tag' => 'br'));
		
		$this->assertEqual($br->format(), '<br />');
	}
	
	public function testElementClass(){
		$div = new Element(array(
			'class' => 'my classname',
			':caption' => 'Test',
		));
		
		$this->assertTrue($div->hasClass('my'));
		$this->assertFalse($div->hasClass('My'));
		$this->assertTrue($div->hasClass('classname'));
		
		$div->removeClass('my');
		$this->assertFalse($div->hasClass('my'));
		
		$div->addClass('test');
		$this->assertTrue($div->hasClass('test'));
		
		$this->assertEqual($div->format(), '<div class="classname test">Test</div>');
		
		$div->set(array(
			'style' => 'color: #fff; url("test.gif")',
			':caption' => 'Hello World',
		));
		
		$this->assertEqual($div->get('style'), 'color: #fff; url("test.gif")');
		$this->assertEqual($div->format(), '<div class="classname test" style="color: #fff; url(\'test.gif\')">Hello World</div>');
		
		$div = new Element(array(
			'class' => array('my', 'classname'),
			':caption' => 'Test',
		));
		$this->assertTrue($div->hasClass('my'));
		$this->assertTrue($div->hasClass('classname'));
	}
	
	public function testElementException(){
		$el = new Element(array(
			'value' => 'Test',
			':tag' => 'input',
			':validate' => array(
				'id' => true,
			),
		));
		
		$this->assertEqual($el->getValue(), 0);
		
		$this->expectException('ValidatorException');
		
		$el->validate();
	}
	
	public function testElements(){
		$els = new Elements();
		
		$els->addElements(
			new Element(array(
				'name' => 'aelement',
				'class' => 'my classname',
				':tag' => 'a',
				':caption' => 'Test',
			)),
			new InputElement(array(
				'name' => 'text',
				'value' => 'Hello World',
				':caption' => 'Test',
			))
		);
		
		$a = $els->getElement('aelement')->set('id', null);
		$this->assertNull($a->get('id'));
		$this->assertEqual(count($els->getElements()), 2);
		
		$this->assertEqual($els->getValue('text'), 'Hello World');
		$this->assertTrue($els->hasElement('text'));
		$this->assertTrue($els->hasElement($els->getElement('text')));
		$this->assertEqual($els->getElement('text')->getValue(), 'Hello World');
		
		$els->setValue(array(
			'text' => 'myValue',
			'doesnotexist' => 'I do not exist!',
		));
		
		$this->assertEqual($els->getValue('text'), 'myValue');
		$this->assertFalse($els->hasElement('doesnotexist'));
		$this->assertFalse($els->getValue('doesnotexist'));
		
		$this->assertEqual(implode($els->format()), '<a name="aelement" class="my classname">Test</a><label>Test</label><input name="text" value="myValue" type="text" id="'.$els->getElement('text')->get('id').'" />');
		
		$els->removeElement('aelement');
		$this->assertFalse($els->hasElement('aelement'));
		$this->assertEqual(count($els->getElements()), 1);
	}
	
	public function testFormElement(){
		$form = new FormElement;
		
		$this->assertEqual(implode(Hash::flatten($form->format())), '<form method="post"><div></div></form>');
		
		$form->addElement(new InputElement(array('name' => 'title')));
		
		$this->assertTrue($form->hasInstance('InputElement'));
		$this->assertTrue($form->hasInstance('Element'));
	}
	
	public function testFormEnctype(){
		$form = new FormElement;
		
		$form->addElement(
			new InputElement(array(
				'name' => 'title',
				'value' => 'Hello World',
				':caption' => 'Title',
			))
		);
		
		$form->format();
		$this->assertNull($form->get('enctype'));
		
		$form->addElement(
			new UploadElement(array(
				'name' => 'myUpload',
			))
		);
		
		$form->format();
		$this->assertEqual($form->get('enctype'), 'multipart/form-data');
	}
	
	public function testInputElement(){
		$input = new InputElement(array(
			'name' => 'title',
			'value' => 'Hello World',
			':caption' => 'Title',
		));
		
		$this->assertEqual($input->format(), '<label>Title</label><input name="title" value="Hello World" type="text" id="'.$input->get('id').'" />');
	}
	
	public function testUploadElement(){
		$input = new UploadElement(array(
			'name' => 'title',
			':caption' => 'Title',
		));
		
		$this->assertEqual($input->format(), '<label>Title</label><input name="title" type="file" id="'.$input->get('id').'" />');
	}
	
	public function testHiddenElement(){
		$input = new HiddenElement(array(
			'name' => 'title',
			'value' => 'Hello World',
		));
		
		$this->assertEqual($input->format(), '<input name="title" value="Hello World" type="hidden" id="'.$input->get('id').'" />');
	}
	
	public function testButtonElement(){
		$button = new ButtonElement(array(
			'name' => 'myButton',
			':caption' => 'Save',
		));
		
		$this->assertEqual($button->format(), '<button name="myButton" type="submit" id="'.$button->get('id').'">Save</button>');
	}
	
	public function testCheckboxElement(){
		$checkbox = new CheckboxElement(array(
			'name' => 'myCheckbox',
			':caption' => 'Checkbox',
		));
		$this->assertEqual($checkbox->format(), '<label><input name="myCheckbox" type="checkbox" value="1" id="'.$checkbox->get('id').'" class="checkbox" /> Checkbox</label>');
		
		$this->assertFalse($checkbox->getValue());
		
		$checkbox->setValue(true);
		$this->assertTrue($checkbox->getValue());
		
		$this->assertEqual($checkbox->format(), '<label><input name="myCheckbox" type="checkbox" value="1" id="'.$checkbox->get('id').'" class="checkbox" checked="checked" /> Checkbox</label>');
	}
	
	public function testRadioSelectElement(){
		foreach(array('radioelement', 'selectelement') as $class){
			$el = new $class(array(
				'name' => 'gender',
				'value' => 0,
				':caption' => 'Gender',
				':elements' => array(
					array(
						'value' => 1,
						':caption' => 'Male',
					),
					array(
						'value' => 2,
						':caption' => 'Female',
					),
				),
			));
			
			$this->assertEqual($el->getValue(), 0);
			
			$el->setValue(2);
			$this->assertEqual($el->getValue(), 2);
			
			$el->setValue(3);
			$this->assertEqual($el->getValue(), 0);
		}
		
		$none = array(
			'value' => 3,
			':caption' => 'None',
			':add' => ' (no gender specified)',
		);
		$el->addElement($none);
		
		$el->setValue(3);
		$this->assertEqual($el->getValue(), 3);
		
		$this->assertTrue($el->hasElement($none));
		$this->assertEqual($none, $el->getSelectedElement());
		$this->assertEqual($el->get(':template'), 'Select.php');
		$this->assertEqual(str_replace(array("\n", "\r"), '', $el->format()), '<label>Gender</label><select name="gender" id="'.$el->get('id').'"><option value="1">Male</option><option value="2">Female</option><option value="3" selected="selected">None (no gender specified)</option></select>');
	}

	public function testTextAreaElement(){
		$textarea = new TextAreaElement(array(
			'name' => 'myArea',
			':caption' => 'Content',
			':add' => ' (addition)',
		));
		
		$this->assertEqual($textarea->format(), '<label>Content (addition)</label><textarea name="myArea" cols="0" rows="0" id="'.$textarea->get('id').'"></textarea>');
	}
	
	public function testRichTextElement(){
		$richtext = new RichTextElement(array(
			'name' => 'myArea',
			':caption' => 'Content',
		));
		
		$this->assertTrue($richtext->hasClass('richtext'));
	}
}