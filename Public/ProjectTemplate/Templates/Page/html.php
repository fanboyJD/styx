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
	<!-- Remove this following script-tag after including MooTools or overwrite Utility/Script.tpl -->
	<script type="text/javascript">
		window.addEvent = function(type, fn){fn()};
	</script>
	${scripts}
</head>
<body><div>
<?php if(Core::retrieve('setup')) echo Setup::getError('htaccess'); ?>
<a id="logo" href="${app.link}"></a>
<?php
	$user = User::retrieve();
	$action = $user ? 'logout' : 'login';
	
	$PasswordLayer = Layer::retrieve('Password');
?>
<div class="text">
	${styx}
	<?php 
		if(User::hasRight('layer.index.edit.add'))
			echo '<a style="padding-left: 30px;" href="'.Layer::retrieve('Index')->link(null, 'edit').'">${lang.news.add}</a>';
	?>
	<?php 
		if($user) echo '<a style="padding-left: 30px;" href="'.Response::link('admin').'">${lang.admin.admin}</a>';
		
		if($PasswordLayer) echo '<a style="padding-left: 30px;" href="'.$PasswordLayer->link().'">Reset Password</a>';
	?>
	<a style="padding-left: 30px;" href="<?php echo Layer::retrieve('Login')->link(null, $action); ?>">${lang.user.<?php echo $action; ?>}</a>
	<br/>
	<?php
		if(Core::retrieve('setup') && $PasswordLayer){
			$styx = Core::retrieve('styx.link');
			echo '<div class="notice">'.sprintf(Setup::getNotice('secure'), $styx, $styx, $styx).'</div>';
		}
	?>
	<br/>
	${layer | lang.validator.rightsError}
	<div class="clear"></div>
</div>
</div>
</body>
</html>
