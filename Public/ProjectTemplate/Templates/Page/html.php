<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>${app.name}</title>
	<base href="${app.link}"></base>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	${package.style.css}
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="alternate" type="application/rss+xml" title="${rss.title}" href="${rss.link}" />
</head>
<body><div>
<?php /**/ if(Core::retrieve('setup')) echo Setup::getError('htaccess'); ?>
<a id="logo" href="${app.link}"></a>
<div class="text">
	${styx}
	<?php 
		if(User::hasRight('layer.index.edit.add'))
			echo '<a style="padding-left: 30px;" href="'.Layer::retrieve('Index')->link(null, 'edit').'">${lang.news.add}</a>';

		$user = User::retrieve();
		$action = $user ? 'logout' : 'login';
		if($user) echo '<a style="padding-left: 30px;" href="'.Response::link('admin').'">${lang.admin.admin}</a>';
		/**/ $PasswordLayer = Layer::retrieve('Password');
		/**/ if($PasswordLayer) echo '<a style="padding-left: 30px;" href="'.$PasswordLayer->link().'">Reset Password</a>';
	?>
	<a style="padding-left: 30px;" href="<?php echo Layer::retrieve('Login')->link(null, $action); ?>">${lang.user.<?php echo $action; ?>}</a>
	<br/>
	<?php /**/ if(Core::retrieve('setup')) echo Setup::handleSetup(); ?>
	<br/>
	${layer | lang.validator.rightsError}
	<div class="clear"></div>
</div>
</div>
</body>
</html>
