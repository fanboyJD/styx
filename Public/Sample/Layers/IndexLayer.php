<?php
class IndexLayer extends Layer {
	
	public $isIndex = false;
	
	public $usernames; // For: View
	
	public function initialize(){
		return array(
			'table' => 'news',
		);
	}
	
	public function populate(){
		$this->Form->addElements(
			new Input(array(
				'name' => 'title',
				':caption' => Lang::retrieve('title'),
				':validate' => 'specialchars',
			)),
			
			new Textarea(array(
				'name' => 'content',
				':caption' => Lang::retrieve('text'),
				':validate' => array('purify', array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
					'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
				)),
			)),
			
			new Button(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			)),
			
			new Field(array(
				'name' => 'uid',
			)),
			
			new Field(array(
				'name' => 'pagetitle',
			)),
			
			new Field(array(
				'name' => 'time',
			))
		);
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function onSave(){
		if(!User::hasRight('layer.index.edit'))
			throw new ValidatorException('rights');
		
		$this->setValue(array(
			'uid' => $this->editing ? $this->content['uid'] : User::get('id'),
			'time' => $this->editing ? $this->content['time'] : time(),
			'pagetitle' => $this->getPagetitle($this->getValue('title')),
		));
		
		$this->save();
		
		$this->Template->append(Lang::get('news.saved', $this->link($this->getValue('pagetitle'))));
	}
	
	public function onEdit(){
		$this->edit();
		
		if(!User::hasRight('layer.index.edit', $this->editing ? 'modify' : 'add'))
			throw new ValidatorException('rights');
		
		$this->Template->apply('edit')->assign(array(
			'headline' => Lang::retrieve('news.'.($this->editing ? 'modify' : 'add')),
		))->assign($this->format());
	}
	
	public function onDelete($title){
		if(!User::hasRight('layer.index.delete'))
			throw new ValidatorException('rights');
		
		// This way it is required to use "handler;json" in the Querystring. If we leave the "if" out the contenttype "json" would be enforced
		if(Request::retrieve('behaviour')!='json')
			throw new ValidatorException('contenttype');
		
		Response::setContentType('json');
		
		try{
			$this->delete();
			$this->Template->assign(array(
				'out' => 'success',
				'msg' => Lang::retrieve('deleted'),
			));
		}catch(ValidatorException $e){
			$this->Template->assign(array(
				'out' => 'error',
				'msg' => $e->getMessage(),
			));
		}
	}
	
	public function onView($title){
		Response::setDefaultContentType('html', 'xml');
		
		$this->Data->limit(0)->order('time DESC');
		if($title)
			$this->Data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->limit(1);
		else
			$this->isIndex = true;
		
		$users = array();
		foreach($this->Data as $n)
			$users[] = $n['uid'];
		
		foreach(db::select('users')->fields('id, name')->where(Query::in('id', $users))->limit(0) as $user)
			$this->usernames[$user['id']] = $user['name'];
		
		// We check for the used ContentType (xml or html) and assign the correct template for it
		$this->Template->apply((Response::getContentType()=='xml' ? 'xml' : '').'view.php');
	}
	
}