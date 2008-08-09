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
		try{
			$this->validate();
			
			$user = $this->data->where(array(
				'name' => $this->getValue('name'),
				'AND',
				'pwd' => md5(Core::retrieve('secure').$this->getValue('pwd')),
			))->fetch();
			if(!is_array($user)) throw new ValidatorException('login');
			
			$loggedin = User::login($user);
			
			$this->Handler->assign(Lang::get('user.loggedin', $user['name']));
		}catch(ValidatorException $e){
			$this->Handler->assign($e->getMessage());
		}
	}
	
	public function onLogin(){
		$user = User::retrieve();
		
		if($user){
			$this->Handler->assign(Lang::get('user.loggedin', $user['name']));
			return;
		}
		
		$this->edit();
		$this->Handler->template('login')->assign($this->format());
	}
	
	public function onLogout(){
		User::logout();
		
		header('Location: '.Core::retrieve('app.link'));
		die;
	}
	
	public function link($title = null, $event = null, $handler = null){
		if($event=='logout') return 'logout';
		
		return parent::link($title, $event, $handler);
	}
	
}