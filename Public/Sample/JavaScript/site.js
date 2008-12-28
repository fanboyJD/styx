var User = {};
window.addEvent('domready', function(){
	(function(){
		$$('div#languages a').set('tween', {duration: 200}).addEvents({
			mouseenter: function(){
				this.fade(0.5);
			},
			
			mouseleave: function(){
				this.fade(1);
			}
		});
	})();
	(function(){
		$$('ul#menu li a').set({
			morph: {duration: 300},
			events: {
				mouseenter: function(){
					this.morph({
						paddingLeft: 12,
						color: '#2c8113'
					});
				},
				mouseleave: function(){
					this.morph({
						paddingLeft: 7,
						color: '#757575'
					});
				}
			},
			styles: {
				fontWeight: Browser.Engine.webkit ? 'normal' : 'bold'
			}
		});
	})();
	
	(function(){
		$$('a.hicon, a.modifier').each(function(el){
			if(el.hasClass('hicon'))
				el.set('opacity', 0).addEvents({
					mouseenter: el.fade.bind(el, 0.5),
					mouseleave: el.fade.bind(el, 0.8)
				}).getParent('div').addEvents({
					mouseenter: el.fade.bind(el, 0.8),
					mouseleave: el.fade.bind(el, 0)
				});
			
			if(el.hasClass('delete'))
				el.addEvent('click', function(e){
					e.stop();
					if(confirm(el.getElement('img').get('title'))){
						var data = {};
						data[el.get('rel')] = User.session;
						
						new Request.JSON({
							url: el,
							data: data,
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
					}
				});
		});
	})();
});