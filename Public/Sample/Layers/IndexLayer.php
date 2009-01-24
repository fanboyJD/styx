<?php
class IndexLayer extends Layer {
	
	public $isIndex = false;
	
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
				':validate' => array(
					'sanitize' => true,
					'notempty' => true,
				),
			)),
			
			new Textarea(array(
				'name' => 'content',
				':caption' => Lang::retrieve('text'),
				':validate' => array(
					'purify' => array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
						'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
					),
					'notempty' => true,
				),
			)),
			
			new UploadInput(array(
				'name' => 'image',
				':caption' => Lang::retrieve('news.file'),
				':alias' => true,
			)),
			
			new Button(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			)),
			
			new Field('uid'),
			new Field('pagetitle'),
			new Field('time'),
			new Field('picture')
		);
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function onSave(){
		if(!User::hasRight('layer.index.edit'))
			throw new ValidatorException('rights');
		
		if(Upload::exists('image')){
			$img = new Image(Upload::move('image', 'Files/', array('size' => 1024*512, 'mimes' => array('image/gif', 'image/png', 'image/jpeg'))));
			$file = $img->resize(120)->save();
			
			$filename = basename($img->getPathname());
			$this->setValue(array(
				'picture' => 'Files/'.$filename,
			));
		}else{
			$this->setValue(array(
				'picture' => $this->editing ? $this->content['picture'] : '',
			));
		}
		
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
		
		// This way it is required to use "handler;json" in the Querystring. If we left the "if" out the contenttype "json" would be enforced
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
		
		// We check for the used ContentType (xml or html) and assign the correct template for it
		$contenttype = Response::getContentType();
		
		$this->Data->fields('news.*, users.name')->join('news.uid=users.id', 'users', 'left')->limit(0)->order('time DESC');
		if($title){
			$this->Data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->limit(1);
			
			if(!count($this->Data))
				throw new ValidatorException('newsnotavailable');
		}elseif($contenttype=='html'){
			$this->paginate()->initialize($this->Data, array(
				'per' => 2,
			));
			
			$this->isIndex = true;
		}elseif($contenttype=='xml'){
			$this->Data->limit(10);
		}
		
		$this->Template->apply(($contenttype=='xml' ? 'xml' : '').'view.php');
	}
	
}