<?php
class LoginLayer extends Layer {
	
	protected $Form;
	
	protected function initialize(){
		$this->setDefaultEvent('save', 'handle');
		$this->setDefaultEvent('edit', 'login');
		$this->setDefaultEvent('view', 'login');
		$this->setReboundEvent('handle', 'login'); // Not really needed here, but still nice =)
		
		return array(
			'model' => 'user',
		);
	}
	
	protected function access(){
		if(in_array($this->event, array('handle', 'login')))
			$this->Form = new FormElement(array(
				'action' => $this->link(null, 'handle'),
				':elements' => array(
					new InputElement(array(
						'name' => 'name',
						':caption' => Lang::retrieve('user.name'),
						':validate' => array(
							'pagetitle' => true,
						),
					)),
					
					new InputElement(array(
						'name' => 'pwd',
						'type' => 'password',
						':caption' => Lang::retrieve('user.pwd'),
					)),
					
					new ButtonElement(array(
						'name' => 'bsave',
						':caption' => Lang::retrieve('user.login'),
					)),
				),
			));
		
		if($this->event=='handle') $this->Form->setValue($this->post);
		
		return true;
	}
	
	public function onHandle(){
		$this->Form->validate();
		
		$user = $this->Model->findByNameAndPwd($this->Form->getValue('name'), $this->Form->getValue('pwd'));
		if(!$user) throw new ValidatorException('login');
		User::login($user);
		
		$this->Template->append(Lang::get('user.loggedin', $user['name']));
	}
	
	public function onLogin(){
		$user = User::retrieve();
		
		if($user){
			$this->Template->append(Lang::get('user.loggedin', $user['name']));
			return;
		}
		
		$this->Template->apply('login')->assign($this->Form->format());
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