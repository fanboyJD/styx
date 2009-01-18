<?php
class LoginLayer extends Layer {
	
	public function initialize(){
		$this->setDefaultEvent('save', 'handle');
		$this->setDefaultEvent('edit', 'login');
		$this->setDefaultEvent('view', 'login');
		$this->setReboundEvent('handle', 'login'); // Not really needed here, but still nice =)
		
		$this->Form->addElements(
			new Input(array(
				'name' => 'name',
				':caption' => Lang::retrieve('user.name'),
				':validate' => array(
					'pagetitle' => true,
				),
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
		);
		
		return array(
			'table' => 'users',
			'identifier' => 'name',
		);
	}
	
	public function onHandle(){
		$data = $this->validate();
		
		$user = $this->Data->where(array(
			'name' => $data['name'],
			'AND',
			'pwd' => User::getPassword($data['pwd']),
		))->fetch();
		
		if(!is_array($user)) throw new ValidatorException('login');
		
		User::login($user);
		
		$this->Template->append(Lang::get('user.loggedin', $user['name']));
	}
	
	public function onLogin(){
		$user = User::retrieve();
		
		if($user){
			$this->Template->append(Lang::get('user.loggedin', $user['name']));
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