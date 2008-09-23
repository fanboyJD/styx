<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>${app.name}</title>
	<base href="${app.link}"></base>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	${package.style.css}
	${package.ie.css}
	${package.ie.js}
	${package.package1.js}
	${scripts}
	<link rel="alternate" type="application/rss+xml" title="${rss.title}" href="${rss.link}" />
</head>
<body>
<div class="wrapper">
	<div id="logo"><a href="${app.link}"></a></div>
	${menu}
	<?php
		if(Layer::retrieve('index')->isIndex)
			echo '<div id="content">${lang.framework.description}</div>';
	?>
	<div id="text">
		${layer}
	</div>
</div>
<div id="footer">
	<div>${user}</div>
	<div style="float: left; margin-left: 10px;">
	<a href="${source}" target="trac">${lang.source}</a>
	<small>${styx}</small>
	</div>
</div>
</body>
</html>
