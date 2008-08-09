var User = {};
window.addEvent('domready', function(){
	(function(){
		$$('ul#menu li a').set({
			morph: {duration: 300},
			events: {
				mouseenter: function(){
					this.morph('ul#menu li#'+this.getParent().id+'_hover a');
				},
				mouseleave: function(){
					this.morph('ul#menu li a');
				}
			}
		});
	})();
	
	(function(){
		$$('a.hicon').set('opacity', 0).each(function(el){
			el.getParent('div').addEvents({
				mouseenter: el.fade.bind(el, 0.8),
				mouseleave: el.fade.bind(el, 0)
			});
			
			if(el.hasClass('delete'))
				el.addEvent('click', function(e){
					e.stop();
					if(confirm(el.getElement('img').get('title')))
						new Request.JSON({
							url: el,
							data: {
								session: User.session,
							},
							onSuccess: function(j){
								if(j && j.out){
									alert(j.msg);
									if(j.out=='success')
										el.getParent('div').fade(0).get('tween').chain(function(){
											this.element.destroy();
										});
								}
							}
						}).post();
				});
		});
	})();
});