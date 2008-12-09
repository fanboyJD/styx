<?php
class LoginLayer extends Layer {
	
	public function initialize(){
		$this->setDefaultEvent('save', 'handle');
		$this->setDefaultEvent('view', 'login');
		
		return array(
			'table' => 'users',
			'form' => new Form(
				new Input(array(
					'name' => 'name',
					':caption' => Lang::retrieve('user.name'),
				)),
				
				new Input(array(
					'name' => 'pwd',
					'type' => 'password',
					':caption' => Lang::retrieve('user.pwd'),
				)),
				
				new Button(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('user.login'),
				))
			)
		);
	}
	
	public function onHandle(){
		$this->validate();
		
		$user = $this->Data->where(array(
			'name' => $this->getValue('name'),
			'AND',
			'pwd' => sha1(Core::retrieve('secure').$this->getValue('pwd')),
		))->fetch();
		
		if(!is_array($user)) throw new ValidatorException('login');
		
		$loggedin = User::login($user);
		
		$this->Template->assign(Lang::get('user.loggedin', $user['name']));
	}
	
	public function onLogin(){
		$user = User::retrieve();
		
		if($user){
			$this->Template->assign(Lang::get('user.loggedin', $user['name']));
			return;
		}
		
		$this->add();
		$this->Template->apply('login')->assign($this->format());
	}
	
	public function onLogout(){
		User::logout();
		
		header('Location: '.Core::retrieve('app.link'));
		die;
	}
	
	public function link($title = null, $event = null, $options = null){
		if($event=='logout') return Response::link('logout');
		
		return parent::link($title, $event, $options);
	}
	
}